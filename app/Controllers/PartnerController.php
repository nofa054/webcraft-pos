<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\CustomerModel;
use App\Models\SupplierModel;

class PartnerController extends BaseController
{
    private CustomerModel $customerModel;
    private SupplierModel $supplierModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new CustomerModel();
        $this->supplierModel = new SupplierModel();
    }

    public function customers(): void
    {
        Auth::requireLogin();

        $search = trim((string) ($_GET['search'] ?? ''));
        $customers = $this->customerModel->getAll($search);
        $nextCode = $this->customerModel->generateNextCode();

        $this->render('partners/customers', [
            'customers' => $customers,
            'search'    => $search,
            'nextCode'  => $nextCode,
        ], 'Manajemen Pelanggan');
    }

    public function storeCustomer(): void
    {
        Auth::requireLogin();
        Csrf::requireValidToken();

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            set_flash('danger', 'Nama pelanggan wajib diisi.');
            redirect(route_url('customers'));
        }

        $this->customerModel->create($_POST);
        set_flash('success', "Pelanggan '{$name}' berhasil ditambahkan.");
        redirect($_SERVER['HTTP_REFERER'] ?? route_url('customers'));
    }

    public function updateCustomer(): void
    {
        Auth::requireLogin();
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($id <= 0 || $name === '') {
            set_flash('danger', 'Nama pelanggan wajib diisi.');
            redirect(route_url('customers'));
        }

        $this->customerModel->update($id, $_POST);
        set_flash('success', "Data pelanggan '{$name}' berhasil diperbarui.");
        redirect(route_url('customers'));
    }

    public function deleteCustomer(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 1) {
            set_flash('warning', 'Pelanggan Umum (Default) tidak dapat dihapus.');
            redirect(route_url('customers'));
        }

        $this->customerModel->delete($id);
        set_flash('success', 'Data pelanggan berhasil dihapus.');
        redirect(route_url('customers'));
    }

    public function suppliers(): void
    {
        Auth::requireLogin();

        $search = trim((string) ($_GET['search'] ?? ''));
        $suppliers = $this->supplierModel->getAll($search);
        $nextCode = $this->supplierModel->generateNextCode();

        $this->render('partners/suppliers', [
            'suppliers' => $suppliers,
            'search'    => $search,
            'nextCode'  => $nextCode,
        ], 'Manajemen Supplier / Pemasok');
    }

    public function storeSupplier(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            set_flash('danger', 'Nama supplier wajib diisi.');
            redirect(route_url('suppliers'));
        }

        $this->supplierModel->create($_POST);
        set_flash('success', "Supplier '{$name}' berhasil ditambahkan.");
        redirect(route_url('suppliers'));
    }

    public function updateSupplier(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($id <= 0 || $name === '') {
            set_flash('danger', 'Nama supplier wajib diisi.');
            redirect(route_url('suppliers'));
        }

        $this->supplierModel->update($id, $_POST);
        set_flash('success', "Data supplier '{$name}' berhasil diperbarui.");
        redirect(route_url('suppliers'));
    }

    public function deleteSupplier(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $this->supplierModel->delete($id);
        set_flash('success', 'Data supplier berhasil dihapus.');
        redirect(route_url('suppliers'));
    }
}

