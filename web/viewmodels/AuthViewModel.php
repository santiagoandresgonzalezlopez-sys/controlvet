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

        $sesion = null;
        if ($validator->passes()) {
            // Las credenciales se verifican en el microservicio auth, que emite el JWT.
            $sesion = Usuario::autenticar($post['email'], $post['password']);
            if (!$sesion) {
                $validator->custom(false, 'general', 'Correo o contraseña incorrectos.');
            }
        }

        if (!$validator->passes() || !$sesion) {
            $errores = $validator->errors();
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        [$usuario, $token] = $sesion;
        Auth::login($usuario, $token);
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
