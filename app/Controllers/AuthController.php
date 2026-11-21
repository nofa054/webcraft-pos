<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(route_url('dashboard'));
        }
        $this->renderStandalone('auth/login', [
            'pageTitle' => 'Login Sistem Kasir',
        ]);
    }

    public function processLogin(): void
    {
        Csrf::requireValidToken();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            set_flash('danger', 'Username dan password wajib diisi.');
            redirect(route_url('login'));
        }

        $result = Auth::login($username, $password);
        if (!$result['success']) {
            set_flash('danger', $result['message']);
            redirect(route_url('login'));
        }

        set_flash('success', 'Selamat datang kembali, ' . $result['user']['name'] . '!');
        redirect(route_url('dashboard'));
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        set_flash('info', 'Anda telah berhasil keluar dari sistem.');
        redirect(route_url('login'));
    }

    public function updateProfile(): void
    {
        Auth::requireLogin();
        Csrf::requireValidToken();

        $userId = Auth::id();
        $userModel = new UserModel();
        $currentUser = $userModel->findById($userId);

        if (!$currentUser) {
            redirect(route_url('logout'));
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if ($name === '') {
            set_flash('danger', 'Nama lengkap tidak boleh kosong.');
            redirect($_SERVER['HTTP_REFERER'] ?? route_url('dashboard'));
        }

        if ($newPassword !== '' && strlen($newPassword) < 6) {
            set_flash('danger', 'Password baru minimal harus terdiri dari 6 karakter.');
            redirect($_SERVER['HTTP_REFERER'] ?? route_url('dashboard'));
        }

        $userModel->update($userId, [
            'name'      => $name,
            'username'  => $currentUser['username'],
            'email'     => $email,
            'role'      => $currentUser['role'],
            'is_active' => $currentUser['is_active'],
            'password'  => $newPassword,
        ]);

        Auth::refreshSessionUser($userId);
        set_flash('success', 'Profil akun Anda berhasil diperbarui.');
        redirect($_SERVER['HTTP_REFERER'] ?? route_url('dashboard'));
    }
}

