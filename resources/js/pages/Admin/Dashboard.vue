<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import type { PageProps, User } from '../../types';

const page = usePage<PageProps>();
const user = computed<User>(() => page.props.auth.user as User);
</script>

<template>
    <AppLayout title="Panel Administrator">
        <Head title="Panel Admin - ALDI-MOTOR" />

        <div class="space-y-6">
            <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-semibold uppercase tracking-wider mb-2 border border-purple-500/30">
                            <span>🛡️ Mode Administrator Bengkel</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Selamat Datang, {{ user.name }}
                        </h1>
                        <p class="text-slate-300 text-sm mt-1">
                            Anda berada di area manajemen operasional <strong class="text-white">ALDI-MOTOR</strong>.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            href="/profile"
                            class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur border border-white/10 transition"
                        >
                            Pengaturan Akun
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Reservasi Hari Ini</span>
                    <span class="text-2xl font-black text-slate-900">8 Slot</span>
                    <span class="text-[11px] text-emerald-600 font-semibold block mt-1">✓ Kapasitas optimal</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Servis Berlangsung</span>
                    <span class="text-2xl font-black text-amber-600">3 Motor</span>
                    <span class="text-[11px] text-slate-400 block mt-1">Ditangani 2 mekanik</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Peringatan Stok Suku Cadang</span>
                    <span class="text-2xl font-black text-red-600">2 Item</span>
                    <span class="text-[11px] text-red-500 font-semibold block mt-1">Di bawah batas stok minimal</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Pelanggan Aktif</span>
                    <span class="text-2xl font-black text-purple-600">142</span>
                    <span class="text-[11px] text-slate-400 block mt-1">Terdaftar di sistem</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Modul Operasional Admin</h2>
                        <p class="text-xs text-slate-500">Akses cepat menu manajemen bengkel ALDI-MOTOR</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100/80 transition">
                        <span class="text-lg mb-2 block">📅</span>
                        <h3 class="font-bold text-sm text-slate-900">Atur Slot Waktu</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola jam operasional, kuota servis harian, dan ketersediaan teknisi.</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100/80 transition">
                        <span class="text-lg mb-2 block">📦</span>
                        <h3 class="font-bold text-sm text-slate-900">Inventaris Sparepart</h3>
                        <p class="text-xs text-slate-500 mt-1">Kontrol stok minimal, lokasi rak penyimpanan, dan histori mutasi barang.</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100/80 transition">
                        <span class="text-lg mb-2 block">👥</span>
                        <h3 class="font-bold text-sm text-slate-900">Manajemen Pengguna & Staf</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola akun mekanik, status blokir pelanggan (no-show), dan hak akses.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
