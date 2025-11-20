<?php
namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Services\NotificationService;
use App\Services\FileHandlerService;
use Exception;

class EmailReconciliationService
{
    private TransactionRepository $txRepository;
    private NotificationService $notificationService;
    private FileHandlerService $fileHandler;
    private $mailbox;

    private const TOLERANCIA_HORAS = 72;

    public function __construct(
        TransactionRepository $txRepository,
        NotificationService $notificationService,
        FileHandlerService $fileHandler
    ) {
        $this->txRepository = $txRepository;
        $this->notificationService = $notificationService;
        $this->fileHandler = $fileHandler;
    }

    public function procesarCorreosNoLeidos()
    {
        if (!defined('IMAP_HOST')) {
            echo "Configuración IMAP faltante.\n";
            return;
        }

        $port = defined('IMAP_PORT') ? IMAP_PORT : 993;
        $connectionString = '{' . IMAP_HOST . ':' . $port . '/imap/ssl}INBOX';

        $this->mailbox = imap_open($connectionString, IMAP_USER, IMAP_PASS);

        if (!$this->mailbox) {
            error_log("Error Conexión IMAP: " . imap_last_error());
            return;
        }

        $emails = imap_search($this->mailbox, 'UNSEEN');

        if ($emails) {
            sort($emails);

            foreach ($emails as $emailId) {
                try {
                    $this->procesarCorreoIndividual($emailId);
                } catch (Exception $e) {
                    error_log("Excepción en email $emailId: " . $e->getMessage());
                }
            }
        }

        imap_close($this->mailbox);
    }

    private function procesarCorreoIndividual($emailId)
    {
        $overview = imap_fetch_overview($this->mailbox, $emailId, 0);
        $header = imap_headerinfo($this->mailbox, $emailId);

        $asunto = isset($overview[0]->subject) ? $this->decodeHeader($overview[0]->subject) : '';
        $remitenteAddr = ($header->from[0]->mailbox ?? '') . "@" . ($header->from[0]->host ?? '');

        $messageId = $header->message_id ?? md5($asunto . ($overview[0]->date ?? '') . $remitenteAddr);

        if ($this->txRepository->isEmailProcessed($messageId)) {
            echo " - Email ya procesado. Saltando.\n";
            return;
        }

        $cuerpoHTML = $this->getBody($emailId);
        $cuerpoTexto = strip_tags($cuerpoHTML);

        $datos = $this->analizarContenido($cuerpoTexto, $asunto, $remitenteAddr);

        if ($datos['es_comprobante'] && $datos['monto'] > 0) {
            echo " + Detectado: {$datos['banco']} | Monto: $ " . number_format($datos['monto'], 0, ',', '.') . "\n";
            $match = $this->buscarOrdenCandidata($datos);

            if ($match) {
                echo "   -> ¡MATCH! Orden #{$match['TransaccionID']}\n";
                $archivoPath = $this->fileHandler->saveEmailAsReceipt($cuerpoHTML, $match['TransaccionID']);
                $this->conciliarOrden($match, $datos, $archivoPath, $messageId);

            } else {
                echo "   -> Sin match automático. Alerta enviada al Admin.\n";
                $this->notificationService->notifyAdminUnreconciledTransfer(
                    $datos['banco'],
                    $datos['monto'],
                    $remitenteAddr,
                    $cuerpoHTML
                );
            }
        }
        imap_setflag_full($this->mailbox, $emailId, "\\Seen");
    }

    private function analizarContenido(string $texto, string $asunto, string $remitente): array
    {
        $datos = [
            'es_comprobante' => false,
            'monto' => 0.0,
            'banco' => 'Desconocido',
            'ids_encontrados' => [],
        ];

        $textoCompleto = $asunto . " " . $texto;
        $textoLow = strtolower($textoCompleto);
        $remitenteLow = strtolower($remitente);

        if (strpos($remitenteLow, 'santander') !== false || strpos($textoLow, 'banco santander') !== false) {
            $datos['banco'] = 'Santander';
            $datos['es_comprobante'] = true;
        } elseif (strpos($remitenteLow, 'bancoestado') !== false || strpos($textoLow, 'caja vecina') !== false) {
            $datos['banco'] = 'BancoEstado';
            $datos['es_comprobante'] = true;
        } elseif (strpos($remitenteLow, 'bancochile') !== false || strpos($textoLow, 'banco de chile') !== false) {
            $datos['banco'] = 'Banco de Chile';
            $datos['es_comprobante'] = true;
        } elseif (strpos($remitenteLow, 'bci') !== false) {
            $datos['banco'] = 'BCI';
            $datos['es_comprobante'] = true;
        } elseif (strpos($textoLow, 'transferencia') !== false && strpos($textoLow, 'comprobante') !== false) {
            $datos['banco'] = 'Banco Genérico';
            $datos['es_comprobante'] = true;
        }

        if (!$datos['es_comprobante'])
            return $datos;

        $textoLimpio = preg_replace('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', '', $textoCompleto);
        $textoLimpio = preg_replace('/\d{1,2}:\d{2}(:\d{2})?/', '', $textoLimpio);
        $textoLimpio = preg_replace('/[\d\.]+\-[\dkK]/', '', $textoLimpio);
        $textoLimpio = preg_replace('/\d+-\d+/', '', $textoLimpio);

        if (preg_match('/(?:\$|CLP|monto|valor|transferencia)[:\s]*([\d\.]+)/i', $textoLimpio, $matches)) {
            $montoStr = str_replace('.', '', $matches[1]);
            $datos['monto'] = (float) $montoStr;
        }
        if (preg_match_all('/(?:orden|pedido|transaccion|tx|pago)\s?#?(\d{1,6})/i', $textoCompleto, $matches)) {
            $datos['ids_encontrados'] = array_unique(array_map('intval', $matches[1]));
        }

        return $datos;
    }

    private function buscarOrdenCandidata(array $datos): ?array
    {
        $candidatas = $this->txRepository->findPendingByAmount($datos['monto'], self::TOLERANCIA_HORAS);

        if (empty($candidatas))
            return null;

        if (count($candidatas) === 1) {
            return $candidatas[0];
        }

        foreach ($candidatas as $orden) {
            if (in_array($orden['TransaccionID'], $datos['ids_encontrados'])) {
                return $orden;
            }
        }
        return null;
    }

    private function conciliarOrden(array $orden, array $datosEmail, string $comprobantePath, string $messageId)
    {
        $estadoEnProcesoID = 3;

        $this->txRepository->updateStatusToProcessingWithProof(
            $orden['TransaccionID'],
            $estadoEnProcesoID,
            $comprobantePath,
            $messageId
        );

        $this->notificationService->logAdminAction(
            1,
            'Auto-Conciliación (Bot)',
            "Orden #{$orden['TransaccionID']} aprobada. Banco: {$datosEmail['banco']}. Monto: {$datosEmail['monto']}"
        );

        $this->notificationService->sendPaymentConfirmationToClientWhatsApp($orden);
    }

    private function decodeHeader($text)
    {
        $elements = imap_mime_header_decode($text);
        $str = '';
        foreach ($elements as $element)
            $str .= $element->text;
        return $str;
    }

    private function getBody($uid)
    {
        $body = $this->getPart($uid, "TEXT/HTML");
        if ($body == "")
            $body = $this->getPart($uid, "TEXT/PLAIN");
        return $body;
    }

    private function getPart($uid, $mimetype, $structure = false, $partNumber = false)
    {
        if (!$structure)
            $structure = imap_fetchstructure($this->mailbox, $uid);
        if ($structure) {
            if ($mimetype == $this->getMimeType($structure)) {
                if (!$partNumber)
                    $partNumber = 1;
                $text = imap_fetchbody($this->mailbox, $uid, $partNumber);
                if ($structure->encoding == 3)
                    return imap_base64($text);
                elseif ($structure->encoding == 4)
                    return imap_qprint($text);
                return $text;
            }
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = $partNumber ? $partNumber . "." : "";
                    $data = $this->getPart($uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data)
                        return $data;
                }
            }
        }
        return false;
    }

    private function getMimeType($structure)
    {
        $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
        if ($structure->subtype)
            return $primaryMimetype[(int) $structure->type] . "/" . $structure->subtype;
        return "TEXT/PLAIN";
    }
}