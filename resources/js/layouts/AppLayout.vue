<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps, User } from '../types';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);
const flash = computed(() => page.props.flash || {});

const dashboardUrl = computed(() => {
    if (!user.value) return '/';
    return user.value.role === 'admin' ? '/admin' : '/beranda';
});

const roleBadgeClass = computed(() => {
    return user.value?.role === 'admin'
        ? 'bg-purple-100 text-purple-800 border-purple-200'
        : 'bg-emerald-100 text-emerald-800 border-emerald-200';
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col text-slate-800">
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-6">
                        <Link :href="dashboardUrl" class="flex items-center gap-2 group">
                            <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center text-white shadow-md shadow-red-500/20 group-hover:scale-105 transition">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-extrabold text-lg tracking-tight text-slate-900">ALDI<span class="text-red-600">MOTOR</span></span>
                                <span class="hidden sm:inline-block text-[11px] font-semibold text-slate-400 uppercase tracking-widest ml-2">Manajemen Bengkel</span>
                            </div>
                        </Link>

                        <nav class="hidden md:flex items-center gap-1.5 pl-4 border-l border-slate-200">
                            <Link :href="dashboardUrl" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">
                                Beranda
                            </Link>
                            <Link href="/reservasi" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-red-600">
                                ⚡ Reservasi Servis
                            </Link>
                            <template v-if="user?.role === 'admin'">
                                <Link href="/admin/services" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Layanan</Link>
                                <Link href="/admin/capacities" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Kapasitas</Link>
                                <Link href="/admin/settings" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Pengaturan</Link>
                            </template>
                            <template v-if="user?.role === 'customer'">
                                <Link href="/beranda/bookings" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Booking Saya</Link>
                                <Link href="/beranda/vehicles" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Garasi Motor</Link>
                            </template>
                            <Link v-if="user" href="/profile" class="px-3 py-1.5 rounded-lg text-xs font-bold transition hover:bg-slate-100 text-slate-700">Profil</Link>
                        </nav>
                    </div>

                    <div class="flex items-center gap-3">
                        <template v-if="user">
                            <span :class="roleBadgeClass" class="px-2.5 py-1 text-xs font-semibold rounded-full border uppercase tracking-wider">
                                {{ user.role_label || user.role }}
                            </span>
                            <Link href="/profile" class="hidden sm:flex flex-col text-right hover:opacity-80 transition">
                                <span class="text-sm font-bold text-slate-900 leading-tight">{{ user.name }}</span>
                                <span class="text-xs text-slate-500 font-mono">{{ user.phone || user.email }}</span>
                            </Link>
                            <Link href="/logout" method="post" as="button" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-red-50 hover:text-red-600 rounded-lg border border-slate-200 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="px-3.5 py-2 text-xs font-bold text-slate-700 hover:text-red-600 transition">Masuk</Link>
                            <Link href="/register" class="px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-xs">Daftar Akun</Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div v-if="flash.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2"><span>{{ flash.success }}</span></div>
            </div>
            <div v-if="flash.error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2"><span>{{ flash.error }}</span></div>
            </div>
        </div>

        <main class="flex-1 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
            &copy; 2026 ALDI-MOTOR &bull; GTNIC Web Development Competition
        </footer>
    </div>
</template>
