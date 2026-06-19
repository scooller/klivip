<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Resultado de cobro' }}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(180deg, #1b0a46 0%, #120534 100%);
            color: #f6efff;
            padding: 24px;
        }

        .card {
            width: min(460px, 100%);
            border: 1px solid rgba(180, 144, 255, 0.35);
            border-radius: 14px;
            padding: 20px;
            background: rgba(35, 14, 87, 0.82);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 1.4rem;
        }

        p {
            margin: 0 0 8px;
            line-height: 1.45;
        }

        .status {
            display: inline-block;
            margin-bottom: 14px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status--ok {
            background: rgba(126, 231, 135, 0.2);
            border: 1px solid rgba(126, 231, 135, 0.5);
            color: #8df3a7;
        }

        .status--ko {
            background: rgba(248, 113, 113, 0.2);
            border: 1px solid rgba(248, 113, 113, 0.5);
            color: #ffacac;
        }

        .meta {
            margin-top: 12px;
            font-size: 0.95rem;
            color: #e7dbff;
        }
    </style>
</head>
<body>
    <article class="card coupon-card shadow-2">
        @if (($status ?? '') === 'redeemed')
            <span class="status status--ok">Cobrado</span>
        @else
            <span class="status status--ko">No cobrado</span>
        @endif

        <h1>{{ $title ?? 'Resultado' }}</h1>
        <p>{{ $message ?? '' }}</p>

        @if(! empty($couponCode))
            <p class="meta"><strong>Cupon:</strong> {{ $couponCode }}</p>
        @endif
        @if(! empty($siteName))
            <p class="meta"><strong>Sitio:</strong> {{ $siteName }}</p>
        @endif
        @if(isset($usedCount))
            <p class="meta"><strong>Usos:</strong> {{ $usedCount }}@if(isset($maxUses) && $maxUses !== null) / {{ $maxUses }} @endif</p>
        @endif
    </article>
</body>
</html>
