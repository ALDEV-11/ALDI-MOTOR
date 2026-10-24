<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-900 flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center z-10">
            <Link href="/" class="inline-flex items-center gap-2 group">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-red-600 to-red-500 flex items-center justify-center shadow-lg shadow-red-500/30 group-hover:scale-105 transition">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="text-left">
                    <span class="block text-2xl font-black tracking-tight text-white">ALDI<span class="text-red-500">MOTOR</span></span>
                    <span class="block text-xs uppercase tracking-widest text-slate-400 font-semibold">Bengkel Modern UMKM</span>
                </div>
            </Link>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md z-10 px-4 sm:px-0">
            <div class="bg-white/95 backdrop-blur-md py-8 px-6 shadow-2xl rounded-2xl border border-slate-100 sm:px-10">
                <slot />
            </div>
            
            <div class="mt-6 text-center text-xs text-slate-500">
                GTNIC 2026 &bull; Accelerating Digital Transformation for SMEs
            </div>
        </div>
    </div>
</template>
