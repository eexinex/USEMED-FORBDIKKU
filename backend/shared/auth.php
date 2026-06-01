<?php
// backend/shared/auth.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connect.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function user_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function login_user(string $role, string $username, string $password): bool
{
    $role = strtolower(trim($role));

    if ($role === 'patient') {
        return login_patient($username, $password);
    }

    if ($role === 'doctor') {
        return login_doctor($username, $password);
    }

    if ($role === 'admin') {
        return login_admin($username, $password);
    }

    return false;
}

function login_patient(string $hn, string $password): bool
{
    $hn = trim($hn);

    $patient = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $hn]
    );

    if ($patient && password_check($password, $patient['password'] ?? '')) {
        $_SESSION['user'] = [
            'id' => (int) $patient['id'],
            'role' => 'patient',
            'hn' => $patient['hn'],
            'username' => $patient['hn'],
            'name' => $patient['full_name'],
        ];

        session_regenerate_id(true);

        return true;
    }

    if ($password === DEMO_PATIENT_PASSWORD && function_exists('demo_patients')) {
        foreach (demo_patients() as $demoPatient) {
            if (strcasecmp((string) $demoPatient['hn'], $hn) === 0) {
                $_SESSION['user'] = [
                    'id' => (int) $demoPatient['id'],
                    'role' => 'patient',
                    'hn' => $demoPatient['hn'],
                    'username' => $demoPatient['hn'],
                    'name' => $demoPatient['full_name'],
                ];

                session_regenerate_id(true);

                return true;
            }
        }
    }

    return false;
}

function login_doctor(string $username, string $password): bool
{
    $username = trim($username);

    $doctor = db_fetch_one(
        'SELECT * FROM doctors WHERE username = :username LIMIT 1',
        ['username' => $username]
    );

    if ($doctor && password_check($password, $doctor['password'] ?? '')) {
        $_SESSION['user'] = [
            'id' => (int) $doctor['id'],
            'role' => 'doctor',
            'username' => $doctor['username'],
            'name' => $doctor['full_name'],
            'license_no' => $doctor['license_no'] ?? '',
        ];

        session_regenerate_id(true);

        return true;
    }

    if ($password === DEMO_DOCTOR_PASSWORD && function_exists('demo_doctors')) {
        foreach (demo_doctors() as $demoDoctor) {
            if (strcasecmp((string) $demoDoctor['username'], $username) === 0) {
                $_SESSION['user'] = [
                    'id' => (int) $demoDoctor['id'],
                    'role' => 'doctor',
                    'username' => $demoDoctor['username'],
                    'name' => $demoDoctor['full_name'],
                    'license_no' => $demoDoctor['license_no'] ?? '',
                    'department' => $demoDoctor['department'] ?? '',
                    'hospital' => $demoDoctor['hospital'] ?? '',
                ];

                session_regenerate_id(true);

                return true;
            }
        }
    }

    return false;
}

function login_admin(string $username, string $password): bool
{
    $username = trim($username);

    $admin = db_fetch_one(
        'SELECT * FROM admin_users WHERE username = :username LIMIT 1',
        ['username' => $username]
    );

    if ($admin && password_check($password, $admin['password'] ?? '')) {
        $_SESSION['user'] = [
            'id' => (int) $admin['id'],
            'role' => 'admin',
            'username' => $admin['username'],
            'name' => $admin['full_name'],
        ];

        session_regenerate_id(true);

        return true;
    }

    if ($username === DEMO_ADMIN_USERNAME && $password === DEMO_ADMIN_PASSWORD) {
        $_SESSION['user'] = [
            'id' => 1,
            'role' => 'admin',
            'username' => 'admin',
            'name' => 'USE MED Admin',
        ];

        session_regenerate_id(true);

        return true;
    }

    return false;
}

function password_check(string $password, string $stored): bool
{
    if ($stored === '') {
        return false;
    }

    $info = password_get_info($stored);

    if (($info['algo'] ?? 0) !== 0) {
        return password_verify($password, $stored);
    }

    return hash_equals($stored, $password);
}

function logout_user(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function require_login(?string $role = null): void
{
    if (!is_logged_in()) {
        if ($role === 'patient') {
            redirect_to('patient/login.php');
        }

        if ($role === 'doctor') {
            redirect_to('doctor/login.php');
        }

        if ($role === 'admin') {
            redirect_to('admin/login.php');
        }

        redirect_to('index.php');
    }

    if ($role !== null && user_role() !== $role) {
        flash_set('danger', 'คุณไม่มีสิทธิ์เข้าหน้านี้');
        redirect_to('index.php');
    }
}

function logout_and_redirect(): void
{
    $role = user_role();
    logout_user();

    if ($role === 'patient') {
        redirect_to('patient/login.php');
    }

    if ($role === 'doctor') {
        redirect_to('doctor/login.php');
    }

    if ($role === 'admin') {
        redirect_to('admin/login.php');
    }

    redirect_to('index.php');
}