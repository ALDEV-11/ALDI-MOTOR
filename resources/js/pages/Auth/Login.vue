<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '../../layouts/GuestLayout.vue';
import InputLabel from '../../components/InputLabel.vue';
import TextInput from '../../components/TextInput.vue';
import InputError from '../../components/InputError.vue';
import PrimaryButton from '../../components/PrimaryButton.vue';

defineProps({
    status: {
        type: String,
        default: '',
    },
});

// Inertia useForm helper untuk mengelola state form
const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

// Fitur kemudahan untuk demonstrasi / penilaian juri GTNIC 2026
const fillDemo = (email: string, pass: string = 'password') => {
    form.email = email;
    form.password = pass;
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk ke Akun" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-slate-900">Masuk ke Sistem</h2>
            <p class="text-xs text-slate-500 mt-1">Silakan masuk dengan akun bengkel atau pelanggan Anda</p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-semibold text-emerald-600 bg-emerald-50 p-3 rounded-lg">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Kata Sandi" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                    />
                    <span>Ingat saya</span>
                </label>
            </div>

            <div>
                <PrimaryButton class="w-full" :disabled="form.processing">
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk Sekarang</span>
                </PrimaryButton>
            </div>
        </form>

        <!-- Demo Akun Quick Filler (Sangat berguna saat presentasi kompetisi) -->
        <div class="mt-6 pt-4 border-t border-slate-200">
            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2 text-center">
                ⚡ Akun Demo Siap Uji (Klik untuk Isi):
            </p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button
                    type="button"
                    @click="fillDemo('admin@aldimotor.com', 'AdminPassword2026!')"
                    class="p-2.5 text-left rounded-xl bg-purple-50/60 hover:bg-purple-100/70 border border-purple-200 transition"
                >
                    <span class="block font-bold text-purple-900">Admin Bengkel</span>
                    <span class="text-[10px] text-purple-700 block truncate">admin@aldimotor.com</span>
                </button>
                <button
                    type="button"
                    @click="fillDemo('siti@demo.id', 'password')"
                    class="p-2.5 text-left rounded-xl bg-slate-50 hover:bg-red-50 border border-slate-200 hover:border-red-300 transition"
                >
                    <span class="block font-bold text-slate-800">Pelanggan (Siti)</span>
                    <span class="text-[10px] text-slate-500 block truncate">siti@demo.id</span>
                </button>
                <button
                    type="button"
                    @click="fillDemo('budi@demo.id', 'password')"
                    class="p-2.5 text-left rounded-xl bg-slate-50 hover:bg-red-50 border border-slate-200 hover:border-red-300 transition"
                >
                    <span class="block font-bold text-slate-800">Pelanggan (Budi)</span>
                    <span class="text-[10px] text-slate-500 block truncate">budi@demo.id</span>
                </button>
                <button
                    type="button"
                    @click="fillDemo('rian@demo.id', 'password')"
                    class="p-2.5 text-left rounded-xl bg-slate-50 hover:bg-red-50 border border-slate-200 hover:border-red-300 transition"
                >
                    <span class="block font-bold text-slate-800">Pelanggan (Rian)</span>
                    <span class="text-[10px] text-slate-500 block truncate">rian@demo.id</span>
                </button>
            </div>
            <p class="text-[10px] text-slate-400 text-center mt-2">
                Admin: <code class="font-mono">AdminPassword2026!</code> | Pelanggan: <code class="font-mono">password</code>
            </p>
        </div>

        <div class="mt-4 text-center text-xs text-slate-600">
            Belum punya akun pelanggan?
            <Link href="/register" class="font-bold text-red-600 hover:text-red-700 underline ml-1">
                Daftar di sini
            </Link>
        </div>
    </GuestLayout>
</template>
