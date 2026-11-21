<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;

class SettingController extends BaseController
{
    public function index(): void
    {
        Auth::requireRole('admin');

        $settings = $this->settingModel->getSettings();
        $this->render('settings/index', [
            'settings' => $settings,
        ], 'Pengaturan Toko & Struk');
    }

    public function update(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $current = $this->settingModel->getSettings();
        $storeName = trim((string) ($_POST['store_name'] ?? ''));

        if ($storeName === '') {
            set_flash('danger', 'Nama toko wajib diisi.');
            redirect(route_url('settings'));
        }

        $logoPath = $current['logo_path'] ?? null;

        // Jika pengguna mencentang hapus logo
        if (!empty($_POST['remove_logo']) && $logoPath) {
            $oldFull = BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $logoPath), DIRECTORY_SEPARATOR);
            if (is_file($oldFull)) {
                @unlink($oldFull);
            }
            $logoPath = null;
        }

        // Proses upload logo toko baru jika ada
        if (!empty($_FILES['store_logo']['name']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['store_logo']['tmp_name'];
            $fileSize = (int) $_FILES['store_logo']['size'];

            if ($fileSize > 2 * 1024 * 1024) {
                set_flash('danger', 'Ukuran file logo maksimal adalah 2 MB.');
                redirect(route_url('settings'));
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpName);
            $allowedMimes = [
                'image/jpeg'    => 'jpg',
                'image/png'     => 'png',
                'image/webp'    => 'webp',
                'image/svg+xml' => 'svg',
            ];

            if (!isset($allowedMimes[$mime])) {
                set_flash('danger', 'Format file logo harus berupa gambar JPG, PNG, WEBP, atau SVG.');
                redirect(route_url('settings'));
            }

            if (!is_dir(UPLOAD_PATH)) {
                mkdir(UPLOAD_PATH, 0775, true);
            }

            $ext = $allowedMimes[$mime];
            $newFileName = 'logo_' . time() . '.' . $ext;
            $destPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $newFileName;

            if (move_uploaded_file($tmpName, $destPath)) {
                if ($logoPath) {
                    $oldFull = BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $logoPath), DIRECTORY_SEPARATOR);
                    if (is_file($oldFull)) {
                        @unlink($oldFull);
                    }
                }
                $logoPath = 'uploads/' . $newFileName;
            }
        }

        $this->settingModel->updateSettings([
            'store_name'               => $storeName,
            'store_tagline'            => $_POST['store_tagline'] ?? '',
            'address'                  => $_POST['address'] ?? '',
            'phone'                    => $_POST['phone'] ?? '',
            'email'                    => $_POST['email'] ?? '',
            'logo_path'                => $logoPath,
            'receipt_header'           => $_POST['receipt_header'] ?? '',
            'receipt_footer'           => $_POST['receipt_footer'] ?? '',
            'show_logo_on_receipt'     => !empty($_POST['show_logo_on_receipt']),
            'show_cashier_on_receipt'  => !empty($_POST['show_cashier_on_receipt']),
            'show_customer_on_receipt' => !empty($_POST['show_customer_on_receipt']),
            'receipt_paper_width'      => $_POST['receipt_paper_width'] ?? '80mm',
        ]);

        set_flash('success', 'Pengaturan toko dan konfigurasi struk berhasil disimpan.');
        redirect(route_url('settings'));
    }
}

