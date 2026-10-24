<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

// Import Chart.js dan vue-chartjs untuk verifikasi visual
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// Dummy data chart untuk verifikasi pemasangan vue-chartjs & chart.js
const chartData = {
    labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
    datasets: [
        {
            label: 'Estimasi Servis Selesai',
            backgroundColor: '#dc2626',
            borderRadius: 6,
            data: [12, 19, 15, 22, 18, 28, 10],
        },
    ],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 5 },
        },
    },
};
</script>

<template>
    <AppLayout title="Dashboard Bengkel">
        <Head title="Dashboard - ALDI-MOTOR" />

        <div class="space-y-6">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600/30 text-red-300 text-xs font-semibold uppercase tracking-wider mb-2">
                            <span>Status Akun: Aktif</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Halo, {{ user.name }}! 👋
                        </h1>
                        <p class="text-slate-300 text-sm mt-1">
                            Selamat datang di portal operasional <strong class="text-red-400">ALDI-MOTOR</strong>. Anda masuk sebagai role
                            <span class="font-bold underline decoration-red-500 uppercase">{{ user.role }}</span>.
                        </p>
                    </div>

                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-4 text-xs space-y-1">
                        <div><span class="text-slate-400">Email:</span> <span class="font-semibold">{{ user.email }}</span></div>
                        <div><span class="text-slate-400">No. WhatsApp:</span> <span class="font-semibold">{{ user.no_hp || '-' }}</span></div>
                        <div><span class="text-slate-400">Waktu Server:</span> <span class="font-semibold">WIB (Asia/Jakarta)</span></div>
                    </div>
                </div>
            </div>

            <!-- Role-Specific Action Hints -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Info 1 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Akses Menu</span>
                        <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold">1</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">
                        <template v-if="user.role === 'pelanggan'">Reservasi & Kendaraan</template>
                        <template v-else-if="user.role === 'mekanik'">Pengerjaan Servis</template>
                        <template v-else-if="user.role === 'pemilik'">Laporan & Analitik</template>
                        <template v-else>Manajemen Master & Slot</template>
                    </h3>
                    <p class="text-xs text-slate-500">
                        Otorisasi middleware server siap mengarahkan setiap aksi sesuai hak role Anda.
                    </p>
                </div>

                <!-- Info 2 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Inertia Stack</span>
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">2</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Single Page App</h3>
                    <p class="text-xs text-slate-500">
                        Navigasi tanpa reload halaman penuh (SPA) lewat Inertia.js dan Vue 3 Composition API.
                    </p>
                </div>

                <!-- Info 3 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Chart Engine</span>
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">3</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Chart.js Terpasang</h3>
                    <p class="text-xs text-slate-500">
                        Pustaka visualisasi grafik aktif untuk mendukung pelaporan analitik UMKM bengkel.
                    </p>
                </div>
            </div>

            <!-- Chart.js Verification Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Grafik Uji Coba: Volume Servis Mingguan</h2>
                        <p class="text-xs text-slate-500">Membuktikan integrasi <code class="bg-slate-100 px-1 py-0.5 rounded text-red-600 font-mono">vue-chartjs</code> & <code class="bg-slate-100 px-1 py-0.5 rounded text-red-600 font-mono">chart.js</code></p>
                    </div>
                    <span class="text-xs px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-semibold">Demo Widget</span>
                </div>
                <div class="h-64 w-full">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
