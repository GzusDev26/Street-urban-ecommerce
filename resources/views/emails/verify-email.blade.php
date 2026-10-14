<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
        }
        .header {
            background: #111111;
            color: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            background: white;
            padding: 40px;
            border-radius: 0 0 8px 8px;
        }
        .content h2 {
            color: #111111;
            margin-top: 0;
            font-size: 20px;
        }
        .content p {
            color: #666;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background: #111111;
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 30px 0;
            font-size: 14px;
        }
        .button:hover {
            background: #1a1a1a;
            text-decoration: none;
        }
        .link-text {
            color: #4285F4;
            text-decoration: none;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #eee;
            margin-top: 30px;
        }
        .email-display {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 4px;
            margin: 20px 0;
            color: #555;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>STREET URBAN</h1>
        </div>

        <div class="content">
            <h2>Activa tu cuenta</h2>

            <p>¡Hola!</p>

            <p>Gracias por registrarte en Street Urban. Para completar tu registro y acceder a todos los beneficios, necesitas verificar tu correo electrónico.</p>

            <div class="email-display">
                📧 {{ $email }}
            </div>

            <p>Haz clic en el botón de abajo para crear tu contraseña y activar tu cuenta:</p>

            <div style="text-align: center;">
                <a href="{{ $activationUrl }}" class="button">Activar Cuenta</a>
            </div>

            <p>O copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #999; font-size: 12px;">
                <a href="{{ $activationUrl }}" class="link-text">{{ $activationUrl }}</a>
            </p>

            <p style="color: #999; font-size: 13px; margin-top: 30px;">
                Este enlace expira en 24 horas.
            </p>

            <div class="footer">
                <p>Si no solicitaste esta cuenta, ignora este correo.</p>
                <p>© {{ date('Y') }} Street Urban. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
