<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\UserModel;

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        Auth::requireRole('admin');

        $search = trim((string) ($_GET['search'] ?? ''));
        $users = $this->userModel->getAll($search);

        $this->render('users/index', [
            'users'  => $users,
            'search' => $search,
        ], 'Manajemen Pengguna & Hak Akses');
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? 'kasir'));

        if ($name === '' || $username === '' || $password === '') {
            set_flash('danger', 'Nama lengkap, username, dan password wajib diisi.');
            redirect(route_url('users'));
        }

        if (strlen($password) < 6) {
            set_flash('danger', 'Password minimal harus terdiri dari 6 karakter.');
            redirect(route_url('users'));
        }

        if ($this->userModel->findByUsername($username)) {
            set_flash('danger', "Username '{$username}' sudah terdaftar. Gunakan username lain.");
            redirect(route_url('users'));
        }

        $this->userModel->create([
            'name'      => $name,
            'username'  => $username,
            'email'     => $_POST['email'] ?? '',
            'password'  => $password,
            'role'      => $role,
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
        ]);

        set_flash('success', "Akun pengguna '{$name}' ({$role}) berhasil dibuat.");
        redirect(route_url('users'));
    }

    public function update(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if (!$user) {
            set_flash('danger', 'Data pengguna tidak ditemukan.');
            redirect(route_url('users'));
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? 'kasir'));
        $isActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        if ($name === '' || $username === '') {
            set_flash('danger', 'Nama lengkap dan username wajib diisi.');
            redirect(route_url('users'));
        }

        if ($password !== '' && strlen($password) < 6) {
            set_flash('danger', 'Password baru minimal harus terdiri dari 6 karakter.');
            redirect(route_url('users'));
        }

        if ($this->userModel->findByUsername($username, $id)) {
            set_flash('danger', "Username '{$username}' sudah digunakan oleh pengguna lain.");
            redirect(route_url('users'));
        }

        // Proteksi agar akun sendiri tidak dinonaktifkan atau diturunkan rolenya
        if ($id === Auth::id() && ($isActive !== 1 || $role !== 'admin')) {
            set_flash('warning', 'Anda tidak dapat menonaktifkan atau mengubah role Admin dari akun Anda sendiri yang sedang aktif.');
            redirect(route_url('users'));
        }

        $this->userModel->update($id, [
            'name'      => $name,
            'username'  => $username,
            'email'     => $_POST['email'] ?? '',
            'password'  => $password,
            'role'      => $role,
            'is_active' => $isActive,
        ]);

        Auth::refreshSessionUser($id);
        set_flash('success', "Data akun '{$name}' berhasil diperbarui.");
        redirect(route_url('users'));
    }

    public function delete(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === Auth::id()) {
            set_flash('warning', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang login.');
            redirect(route_url('users'));
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            set_flash('danger', 'Pengguna tidak ditemukan.');
            redirect(route_url('users'));
        }

        if ($this->userModel->hasRelatedRecords($id)) {
            set_flash('warning', "Pengguna '{$user['name']}' sudah memiliki riwayat transaksi/stok. Silakan nonaktifkan status akunnya agar riwayat tetap terjaga.");
            redirect(route_url('users'));
        }

        $this->userModel->delete($id);
        set_flash('success', "Akun pengguna '{$user['name']}' berhasil dihapus.");
        redirect(route_url('users'));
    }
}

