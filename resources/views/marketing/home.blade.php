<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EasyAI — Self-Hosted AI Workspace</title>
    <meta name="description" content="Private, self-hosted AI workspace powered by Ollama. Multi-tenant, team collaboration, knowledge base, and more." />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: '#6366f1' }
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-glow {
            background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99,102,241,0.3), transparent);
        }
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-300 antialiased">

{{-- ── Navbar ──────────────────────────────────────────────────────── --}}
<nav class="fixed top-0 inset-x-0 z-50 bg-slate-950/80 backdrop-blur border-b border-slate-800">
    <div class="max-w-6xl mx-auto px-5 flex items-center justify-between h-16">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.442 2.798H4.24c-1.47 0-2.442-1.798-1.442-2.798L4.2 15.9" />
                </svg>
            </div>
            <span class="text-white font-bold text-lg">EasyAI</span>
        </div>
        <div class="hidden md:flex items-center gap-6 text-sm">
            <a href="#features" class="hover:text-white transition-colors">Features</a>
            <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
            <a href="#faq" class="hover:text-white transition-colors">FAQ</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}"
               class="text-sm text-slate-400 hover:text-white transition-colors">Sign in</a>
            <a href="{{ route('register') }}"
               class="text-sm px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                Get Started
            </a>
        </div>
    </div>
</nav>

{{-- ── Hero ─────────────────────────────────────────────────────────── --}}
<section class="hero-glow pt-32 pb-24 px-5 text-center">
    <div class="max-w-4xl mx-auto">
        <span class="inline-flex items-center gap-2 text-xs font-medium px-3 py-1.5
                     bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 rounded-full mb-6">
            <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full"></span>
            Powered by Ollama · 100% Private
        </span>

        <h1 class="text-5xl md:text-6xl font-extrabold text-white leading-tight mb-6">
            Your Private<br />
            <span class="gradient-text">AI Workspace</span>
        </h1>

        <p class="text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
            Self-hosted, multi-tenant AI workspace for your team.
            Projects, knowledge base, memory, and billing — all in one platform.
            Your data never leaves your server.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}"
               class="w-full sm:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-500
                      text-white font-semibold rounded-xl transition-colors text-sm">
                Start Free Trial →
            </a>
            <a href="#features"
               class="w-full sm:w-auto px-8 py-3.5 border border-slate-700 hover:border-slate-500
                      text-slate-300 hover:text-white font-semibold rounded-xl transition-colors text-sm">
                See Features
            </a>
        </div>

        <p class="mt-5 text-xs text-slate-600">
            No credit card required · Self-hosted · Your data stays private
        </p>
    </div>
</section>

{{-- ── Features ─────────────────────────────────────────────────────── --}}
<section id="features" class="py-24 px-5">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Everything your team needs
            </h2>
            <p class="text-slate-400 text-lg max-w-xl mx-auto">
                Built for teams that care about privacy, productivity, and control.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['🧠', 'AI Memory', 'Projects remember context across conversations. No more repeating yourself.'],
                ['📁', 'Projects & Chats', 'Organize work into projects. Each chat keeps full conversation history.'],
                ['📚', 'Knowledge Base', 'Upload documents. The AI answers from your own knowledge base.'],
                ['👥', 'Team Collaboration', 'Invite teammates, assign roles, restrict projects per-member.'],
                ['🔒', '100% Private', 'Self-hosted with Ollama. Your data never leaves your infrastructure.'],
                ['💳', 'Flexible Billing', 'COD, bKash/Nagad via SSLCommerz, or international Stripe payments.'],
                ['📄', 'Chat Export', 'Export conversations as PDF or Markdown for documentation.'],
                ['⚡', 'Token Quotas', 'Per-tenant token limits with automatic monthly reset and upgrade prompts.'],
                ['🔑', 'REST API', 'Full API with Sanctum token auth. Build your mobile app on top.'],
            ] as [$icon, $title, $desc])
            <div class="card-hover bg-slate-900 border border-slate-800 rounded-xl p-6">
                <div class="text-3xl mb-4">{{ $icon }}</div>
                <h3 class="text-white font-semibold mb-2">{{ $title }}</h3>
                <p class="text-slate-400 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── How it works ─────────────────────────────────────────────────── --}}
<section class="py-24 px-5 bg-slate-900/50">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">How it works</h2>
        <p class="text-slate-400 mb-16">Up and running in minutes on your own server.</p>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach([
                ['1', 'Deploy', 'Clone the repo, configure .env, run migrations. Works on any VPS or cPanel host.'],
                ['2', 'Invite Your Team', 'Register your workspace, invite teammates, create projects.'],
                ['3', 'Start Chatting', 'Connect to your Ollama models and start getting work done — privately.'],
            ] as [$num, $title, $desc])
            <div class="relative">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center
                            text-white font-bold text-lg mx-auto mb-4">{{ $num }}</div>
                <h3 class="text-white font-semibold mb-2">{{ $title }}</h3>
                <p class="text-slate-400 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Pricing ──────────────────────────────────────────────────────── --}}
<section id="pricing" class="py-24 px-5">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Simple, transparent pricing</h2>
            <p class="text-slate-400">Pay with bKash, Nagad, Stripe, or Cash on Delivery.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($plans as $i => $plan)
            <div class="card-hover relative bg-slate-900 border rounded-xl p-7
                        {{ $i === 1 ? 'border-indigo-500 ring-1 ring-indigo-500/50' : 'border-slate-800' }}">
                @if($i === 1)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                        Most Popular
                    </span>
                </div>
                @endif

                <h3 class="text-white font-bold text-lg mb-1">{{ $plan->name }}</h3>
                <div class="mb-4">
                    <span class="text-4xl font-extrabold text-white">${{ number_format($plan->price, 0) }}</span>
                    <span class="text-slate-400 text-sm">/month</span>
                </div>
                <p class="text-slate-400 text-sm mb-5">
                    {{ number_format($plan->monthly_token_limit / 1000, 0) }}K tokens/month
                </p>

                @if($plan->features)
                <ul class="space-y-2 mb-7">
                    @foreach($plan->features as $feature)
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                @endif

                <a href="{{ route('register') }}"
                   class="block text-center py-2.5 rounded-lg text-sm font-semibold transition-colors
                          {{ $i === 1
                              ? 'bg-indigo-600 hover:bg-indigo-500 text-white'
                              : 'bg-slate-800 hover:bg-slate-700 text-slate-200' }}">
                    Get Started
                </a>
            </div>
            @endforeach
        </div>

        <p class="text-center text-slate-500 text-sm mt-8">
            Need a self-hosted license?
            <a href="#faq" class="text-indigo-400 hover:underline">Contact us →</a>
        </p>
    </div>
</section>

{{-- ── FAQ ──────────────────────────────────────────────────────────── --}}
<section id="faq" class="py-24 px-5 bg-slate-900/50">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-white text-center mb-12">Frequently asked questions</h2>

        <div class="space-y-4">
            @foreach([
                ['Is my data private?', 'Yes. EasyAI is fully self-hosted. All AI processing happens via your own Ollama instance. No data is sent to external AI providers.'],
                ['Which AI models are supported?', 'Any model supported by Ollama — llama3, mistral, codellama, gemma, and more. Switch models per project.'],
                ['Can I use it for my whole team?', 'Yes. EasyAI is multi-tenant. You can invite team members, assign roles (admin/member), and restrict projects to specific members.'],
                ['What payment methods are accepted?', 'Cash on Delivery (COD), bKash / Nagad / local cards via SSLCommerz, and international cards via Stripe.'],
                ['Do I need technical knowledge to deploy?', 'Basic server knowledge is enough. We provide step-by-step deployment guides for cPanel and VPS (Ubuntu 22).'],
            ] as [$q, $a])
            <details class="bg-slate-900 border border-slate-800 rounded-xl p-5 group">
                <summary class="text-white font-medium cursor-pointer list-none flex justify-between items-center">
                    {{ $q }}
                    <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-slate-400 text-sm leading-relaxed">{{ $a }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ── CTA ──────────────────────────────────────────────────────────── --}}
<section class="py-24 px-5 text-center">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
            Ready to own your AI workspace?
        </h2>
        <p class="text-slate-400 mb-8">Start your free trial. No credit card required.</p>
        <a href="{{ route('register') }}"
           class="inline-flex items-center gap-2 px-8 py-4 bg-indigo-600 hover:bg-indigo-500
                  text-white font-semibold rounded-xl transition-colors">
            Create Your Workspace →
        </a>
    </div>
</section>

{{-- ── Footer ───────────────────────────────────────────────────────── --}}
<footer class="border-t border-slate-800 py-10 px-5">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-indigo-600 rounded flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3" />
                </svg>
            </div>
            <span class="text-slate-400 text-sm">EasyAI &copy; {{ date('Y') }}</span>
        </div>
        <div class="flex items-center gap-6 text-sm text-slate-500">
            <a href="#features" class="hover:text-white transition-colors">Features</a>
            <a href="#pricing"  class="hover:text-white transition-colors">Pricing</a>
            <a href="#faq"      class="hover:text-white transition-colors">FAQ</a>
            <a href="{{ route('login') }}" class="hover:text-white transition-colors">Sign in</a>
        </div>
    </div>
</footer>

</body>
</html>