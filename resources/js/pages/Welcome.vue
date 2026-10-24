<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head title="Selamat Datang di ALDI-MOTOR" />

    <div class="min-h-screen bg-slate-900 text-white flex flex-col selection:bg-red-500 selection:text-white">
        <!-- Header -->
        <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-600 to-red-500 flex items-center justify-center shadow-lg shadow-red-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white">ALDI<span class="text-red-500">MOTOR</span></span>
                        <span class="hidden sm:inline-block text-[10px] font-semibold text-slate-400 uppercase tracking-widest ml-2">Smart Workshop SME</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-sm font-semibold transition shadow-md shadow-red-600/30"
                    >
                        Masuk ke Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-4 py-2 text-sm font-semibold text-slate-300 hover:text-white transition"
                        >
                            Masuk
                        </Link>
                        <Link
                            href="/register"
                            class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-sm font-semibold transition shadow-md shadow-red-600/30"
                        >
                            Daftar Pelanggan
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1">
            <div class="relative overflow-hidden py-16 sm:py-24">
                <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-red-600/20 blur-[120px] rounded-full pointer-events-none"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold uppercase tracking-wider mb-6">
                        <span>🏆 GTNIC 2026 &bull; Web Development</span>
                    </div>

                    <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white max-w-4xl mx-auto leading-tight">
                        Akselerasi Digitalisasi Servis Bengkel <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400">UMKM Anda</span>
                    </h1>

                    <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                        Platform pintar manajemen bengkel motor: Reservasi slot online anti-antre, pelacakan servis transparan, manajemen stok suku cadang terintegrasi, dan notifikasi WhatsApp praktis.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/reservasi"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm shadow-xl shadow-red-600/30 transition transform hover:-translate-y-0.5"
                        >
                            Reservasi Servis Sekarang ⚡
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user"
                            href="/login"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-sm transition"
                        >
                            Login Akun Demo
                        </Link>
                    </div>

                    <!-- Role Quick Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-16 text-left">
                        <div class="bg-slate-800/60 border border-slate-700/60 p-6 rounded-2xl hover:border-red-500/40 transition">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4 font-bold text-lg">
                                🛵
                            </div>
                            <h3 class="font-bold text-white text-base mb-1">Pelanggan</h3>
                            <p class="text-xs text-slate-400">Booking jadwal servis kendaraan tanpa ribet, pantau status pengerjaan secara real-time.</p>
                        </div>

                        <div class="bg-slate-800/60 border border-slate-700/60 p-6 rounded-2xl hover:border-red-500/40 transition">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center mb-4 font-bold text-lg">
                                🛠️
                            </div>
                            <h3 class="font-bold text-white text-base mb-1">Admin Bengkel</h3>
                            <p class="text-xs text-slate-400">Kelola master data, atur slot waktu servis harian, stok suku cadang, dan akun teknisi.</p>
                        </div>

                        <div class="bg-slate-800/60 border border-slate-700/60 p-6 rounded-2xl hover:border-red-500/40 transition">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center mb-4 font-bold text-lg">
                                🔧
                            </div>
                            <h3 class="font-bold text-white text-base mb-1">Mekanik</h3>
                            <p class="text-xs text-slate-400">Catat diagnosa kerusakan, update progress servis, dan input pemakaian sparepart otomatis.</p>
                        </div>

                        <div class="bg-slate-800/60 border border-slate-700/60 p-6 rounded-2xl hover:border-red-500/40 transition">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center mb-4 font-bold text-lg">
                                📊
                            </div>
                            <h3 class="font-bold text-white text-base mb-1">Pemilik (Owner)</h3>
                            <p class="text-xs text-slate-400">Pantau performa pendapatan, statistik servis terlaris, dan analitik bisnis visual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
            &copy; 2026 ALDI-MOTOR. Platform Reservasi & Manajemen Servis Bengkel UMKM.
        </footer>
    </div>
</template>
