<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import type { PageProps, User } from '../../types';

const page = usePage<PageProps>();
const user = computed<User>(() => page.props.auth.user as User);
</script>

<template>
    <AppLayout title="Beranda Pelanggan">
        <Head title="Beranda Servis - ALDI-MOTOR" />

        <div class="space-y-6">
            <!-- Header Banner Pelanggan -->
            <div class="bg-gradient-to-r from-red-600 to-rose-700 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-semibold uppercase tracking-wider mb-2 backdrop-blur">
                            <span>🛵 Portal Servis Pelanggan</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Halo, {{ user.name }}!
                        </h1>
                        <p class="text-red-100 text-sm mt-1 max-w-xl">
                            Selamat datang di portal reservasi servis motor Anda di bengkel <strong class="text-white">ALDI-MOTOR</strong>.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            href="/profile"
                            class="px-4 py-2.5 rounded-xl bg-white text-red-600 hover:bg-red-50 text-xs font-bold shadow-md transition"
                        >
                            Pengaturan Profil & WhatsApp
                        </Link>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Notification Status Banner -->
            <div class="rounded-2xl border p-5 flex items-center justify-between"
                 :class="user.whatsapp_opt_in ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg"
                         :class="user.whatsapp_opt_in ? 'bg-emerald-200/60 text-emerald-800' : 'bg-amber-200/60 text-amber-800'">
                        {{ user.whatsapp_opt_in ? '💬' : '⚠️' }}
                    </div>
                    <div>
                        <h4 class="font-bold text-sm">
                            Status WhatsApp: {{ user.whatsapp_opt_in ? 'Aktif Menerima Pembaruan' : 'Notifikasi Dinonaktifkan' }}
                        </h4>
                        <p class="text-xs opacity-90">
                            Nomor terdaftar: <span class="font-mono font-semibold">{{ user.phone }}</span>
                            {{ user.whatsapp_opt_in ? '— Pengingat servis berkala akan dikirimkan otomatis.' : '— Anda tidak akan menerima notifikasi pengerjaan via WhatsApp.' }}
                        </p>
                    </div>
                </div>

                <Link
                    href="/profile"
                    class="text-xs font-bold underline shrink-0 ml-4 hover:opacity-80"
                >
                    Ubah
                </Link>
            </div>

            <!-- Kartu Aksi Cepat Pelanggan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Aksi 1: Buat Reservasi -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between hover:border-red-500/50 transition">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-xl mb-4 font-bold">
                            🗓️
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Booking Jadwal Servis</h3>
                        <p class="text-xs text-slate-500">
                            Pilih tanggal dan slot waktu servis tanpa khawatir antre panjang di bengkel.
                        </p>
                    </div>
                    <div class="mt-6">
                        <Link
                            href="/reservasi"
                            class="w-full py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition block text-center shadow-xs"
                        >
                            + Booking Sekarang
                        </Link>
                    </div>
                </div>

                <!-- Aksi 2: Data Motor Saya -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between hover:border-red-500/50 transition">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl mb-4 font-bold">
                            🏍️
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Garasi Kendaraan Saya</h3>
                        <p class="text-xs text-slate-500">
                            Daftarkan plat nomor, merk, dan tipe motor Anda untuk mempermudah riwayat perawatan.
                        </p>
                    </div>
                    <div class="mt-6">
                        <Link
                            href="/beranda/vehicles"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition block text-center shadow-xs"
                        >
                            Kelola Kendaraan
                        </Link>
                    </div>
                </div>

                <!-- Aksi 3: Riwayat Servis -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between hover:border-red-500/50 transition">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-4 font-bold">
                            📋
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Histori & Booking Saya</h3>
                        <p class="text-xs text-slate-500">
                            Pantau status pengerjaan servis dan batalkan booking terkonfirmasi jika ada kendala.
                        </p>
                    </div>
                    <div class="mt-6">
                        <Link
                            href="/beranda/bookings"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition block text-center"
                        >
                            Lihat Booking Saya
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
