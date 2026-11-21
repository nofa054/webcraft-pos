<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SettingModel
{
    private PDO $db;
    private static ?array $cachedSettings = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        $stmt = $this->db->query("SELECT * FROM store_settings WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch();

        if (!$row) {
            $row = [
                'id'                       => 1,
                'store_name'               => 'Webcraft POS UMKM',
                'store_tagline'            => 'Solusi Kasir & Inventori Terpadu',
                'address'                  => 'Jl. Merdeka Raya No. 88',
                'phone'                    => '0812-3456-7890',
                'email'                    => 'info@webcraftpos.local',
                'logo_path'                => null,
                'receipt_header'           => 'Melayani Eceran & Grosir',
                'receipt_footer'           => 'Terima kasih atas kunjungan Anda!',
                'show_logo_on_receipt'     => 1,
                'show_cashier_on_receipt'  => 1,
                'show_customer_on_receipt' => 1,
                'receipt_paper_width'      => '80mm',
                'currency_symbol'          => 'Rp',
            ];
        }

        self::$cachedSettings = $row;
        return $row;
    }

    public function updateSettings(array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE store_settings
            SET store_name = :store_name,
                store_tagline = :store_tagline,
                address = :address,
                phone = :phone,
                email = :email,
                logo_path = :logo_path,
                receipt_header = :receipt_header,
                receipt_footer = :receipt_footer,
                show_logo_on_receipt = :show_logo_on_receipt,
                show_cashier_on_receipt = :show_cashier_on_receipt,
                show_customer_on_receipt = :show_customer_on_receipt,
                receipt_paper_width = :receipt_paper_width,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");

        $res = $stmt->execute([
            'store_name'               => trim($data['store_name']),
            'store_tagline'            => trim($data['store_tagline'] ?? ''),
            'address'                  => trim($data['address'] ?? ''),
            'phone'                    => trim($data['phone'] ?? ''),
            'email'                    => trim($data['email'] ?? ''),
            'logo_path'                => $data['logo_path'] ?? null,
            'receipt_header'           => trim($data['receipt_header'] ?? ''),
            'receipt_footer'           => trim($data['receipt_footer'] ?? ''),
            'show_logo_on_receipt'     => !empty($data['show_logo_on_receipt']) ? 1 : 0,
            'show_cashier_on_receipt'  => !empty($data['show_cashier_on_receipt']) ? 1 : 0,
            'show_customer_on_receipt' => !empty($data['show_customer_on_receipt']) ? 1 : 0,
            'receipt_paper_width'      => in_array($data['receipt_paper_width'] ?? '80mm', ['58mm', '80mm'], true)
                ? $data['receipt_paper_width']
                : '80mm',
        ]);

        self::$cachedSettings = null;
        return $res;
    }
}

