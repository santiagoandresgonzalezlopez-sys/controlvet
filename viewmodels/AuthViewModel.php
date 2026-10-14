<?php
/**
 * Ruta: controlvet/viewmodels/AuthViewModel.php
 * Orquesta el login/logout: valida credenciales (Modelo), emite sesión+JWT
 * (Auth) y decide qué Vista mostrar.
 */

class AuthViewModel
{
    public function mostrarLogin(): void
    {
        if (Auth::check()) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        $errores = [];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function procesarLogin(array $post): void
    {
        $validator = new Validator();
        $validator->required($post, 'email', 'correo electrónico')
                  ->email($post, 'email')
                  ->required($post, 'password', 'contraseña');

        $usuario = null;
        if ($validator->passes()) {
            $usuario = Usuario::findByEmail($post['email']);
            if (!$usuario || !Usuario::verifyPassword($post['password'], $usuario['password_hash'])) {
                $validator->custom(false, 'general', 'Correo o contraseña incorrectos.');
                $usuario = null;
            }
        }

        if (!$validator->passes() || !$usuario) {
            $errores = $validator->errors();
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        Auth::login($usuario);
        header('Location: index.php?route=dashboard');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?route=login');
        exit;
    }
}
