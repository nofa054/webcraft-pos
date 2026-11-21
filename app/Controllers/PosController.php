<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\CategoryModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\SaleModel;
use App\Models\UserModel;

class PosController extends BaseController
{
    private SaleModel $saleModel;
    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->saleModel = new SaleModel();
        $this->productModel = new ProductModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $products = $this->productModel->getAll(['only_active' => true]);
        $categories = (new CategoryModel())->getAll();
        $customers = (new CustomerModel())->getAll();
        $nextInvoicePreview = $this->saleModel->generateInvoiceNo();

        $this->render('pos/index', [
            'products'           => $products,
            'categories'         => $categories,
            'customers'          => $customers,
            'nextInvoicePreview' => $nextInvoicePreview,
        ], 'Kasir (Point of Sale)');
    }

    public function checkout(): void
    {
        Auth::requireLogin();

        $rawInput = file_get_contents('php://input');
        $jsonPayload = $rawInput ? json_decode($rawInput, true) : null;

        if (is_array($jsonPayload)) {
            $token = $jsonPayload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!Csrf::verify((string) $token)) {
                $this->json([
                    'success' => false,
                    'message' => 'Token keamanan CSRF tidak valid. Silakan muat ulang halaman.',
                ], 419);
            }
            $payload = $jsonPayload;
        } else {
            Csrf::requireValidToken();
            $items = isset($_POST['items']) && is_string($_POST['items'])
                ? json_decode($_POST['items'], true)
                : ($_POST['items'] ?? []);
            $payload = $_POST;
            $payload['items'] = $items;
        }

        try {
            $result = $this->saleModel->createTransaction($payload, Auth::id());
            $receiptUrl = route_url('receipt', ['id' => $result['id']]);

            if (is_array($jsonPayload)) {
                $this->json([
                    'success'     => true,
                    'message'     => 'Transaksi berhasil disimpan!',
                    'data'        => $result,
                    'receipt_url' => $receiptUrl,
                ]);
            }

            set_flash('success', 'Transaksi #' . $result['invoice_no'] . ' berhasil disimpan.');
            redirect($receiptUrl);
        } catch (\Throwable $e) {
            if (is_array($jsonPayload)) {
                $this->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            set_flash('danger', $e->getMessage());
            redirect(route_url('pos'));
        }
    }

    public function receipt(): void
    {
        Auth::requireLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $sale = $this->saleModel->findByIdWithItems($id);

        if (!$sale) {
            set_flash('danger', 'Data struk transaksi tidak ditemukan.');
            redirect(route_url('transactions'));
        }

        $this->renderStandalone('pos/receipt', [
            'sale'      => $sale,
            'autoPrint' => !empty($_GET['print']),
        ]);
    }

    public function history(): void
    {
        Auth::requireLogin();

        $filters = [
            'start_date'     => trim((string) ($_GET['start_date'] ?? date('Y-m-01'))),
            'end_date'       => trim((string) ($_GET['end_date'] ?? date('Y-m-d'))),
            'payment_method' => trim((string) ($_GET['payment_method'] ?? '')),
            'user_id'        => (int) ($_GET['user_id'] ?? 0),
            'search'         => trim((string) ($_GET['search'] ?? '')),
        ];

        $sales = $this->saleModel->getTransactionsReport($filters);
        $users = (new UserModel())->getAll();

        $detailSale = null;
        if (!empty($_GET['detail_id'])) {
            $detailSale = $this->saleModel->findByIdWithItems((int) $_GET['detail_id']);
        }

        $this->render('pos/history', [
            'sales'      => $sales,
            'users'      => $users,
            'filters'    => $filters,
            'detailSale' => $detailSale,
        ], 'Riwayat Transaksi Penjualan');
    }

    public function detailJson(): void
    {
        Auth::requireLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $sale = $this->saleModel->findByIdWithItems($id);

        if (!$sale) {
            $this->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $this->json([
            'success' => true,
            'sale'    => $sale,
        ]);
    }
}

