<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($brand->name ?? config('app.name')) }}</title>
    <style>
        :root {
            --brand-primary: {{ $brand->primary_color ?? '#2563eb' }};
            --brand-secondary: {{ $brand->secondary_color ?? '#1d4ed8' }};
            --brand-accent: {{ $brand->accent_color ?? '#f97316' }};
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }
        header {
            background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary));
            color: #fff;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 2rem;
        }
        header p {
            margin: 0.5rem 0 0;
            opacity: 0.85;
        }
        nav {
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding: 1rem;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }
        nav a {
            text-decoration: none;
            color: var(--brand-primary);
            font-weight: 600;
        }
        main {
            max-width: 960px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-primary);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        }
        .badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            color: var(--brand-primary);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        footer {
            text-align: center;
            padding: 2rem 1rem;
            color: #64748b;
        }
        form label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        form input[type="text"],
        form input[type="email"],
        form textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5f5;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        form input:focus,
        form textarea:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .timeline li {
            border-left: 2px solid var(--brand-primary);
            margin-left: 1rem;
            padding-left: 1.5rem;
            padding-bottom: 1.5rem;
            position: relative;
        }
        .timeline li::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--brand-primary);
        }
        .timeline li:last-child {
            border-color: transparent;
            padding-bottom: 0;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            border: 2px dashed #cbd5f5;
            border-radius: 1rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <header>
        @if($brand->logo_url)
            <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" style="max-height: 64px; margin-bottom: 1rem;">
        @endif
        <h1>{{ $brand->name }}</h1>
        <p>We're here to help. Submit a ticket or browse our knowledge base.</p>
    </header>
    <nav>
        <a href="{{ route('portal.brand.home', $brand->slug) }}">Knowledge Base</a>
        <a href="{{ route('portal.tickets.create', $brand->slug) }}">Submit a Ticket</a>
    </nav>
    <main>
        {{ $slot }}
    </main>
    <footer>
        &copy; {{ now()->year }} {{ $brand->name }}. Powered by Ticketr.
    </footer>
</body>
</html>
