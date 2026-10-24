<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '../../layouts/GuestLayout.vue';
import InputLabel from '../../components/InputLabel.vue';
import TextInput from '../../components/TextInput.vue';
import InputError from '../../components/InputError.vue';
import PrimaryButton from '../../components/PrimaryButton.vue';

// State formulir registrasi pelanggan
const form = useForm({
    name: '',
    email: '',
    phone: '',
    whatsapp_opt_in: true,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Pendaftaran Pelanggan Baru" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-slate-900">Daftar Akun Pelanggan</h2>
            <p class="text-xs text-slate-500 mt-1">
                Khusus pemilik kendaraan motor untuk reservasi servis mudah & transparan
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nama Lengkap -->
            <div>
                <InputLabel for="name" value="Nama Lengkap" />
                <TextInput
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Contoh: Budi Santoso"
                />
                <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
                <InputLabel for="email" value="Alamat Email" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="budi@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Nomor WhatsApp (Phone) -->
            <div>
                <InputLabel for="phone" value="Nomor WhatsApp" />
                <TextInput
                    id="phone"
                    type="tel"
                    v-model="form.phone"
                    required
                    placeholder="Contoh: 081234567890 atau +6281234567890"
                />
                <p class="text-[11px] text-slate-400 mt-1">
                    Format otomatis dinormalisasi ke standar seluler Indonesia (628xxx).
                </p>
                <InputError :message="form.errors.phone" />
            </div>

            <!-- Checkbox Persetujuan Notifikasi WhatsApp -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.whatsapp_opt_in"
                        class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500"
                    />
                    <div class="text-xs text-slate-700 leading-snug">
                        <span class="font-semibold block text-slate-900 mb-0.5">Notifikasi WhatsApp Otomatis</span>
                        Saya bersedia menerima pembaruan status pengerjaan servis dan pengingat servis berkala via WhatsApp.
                    </div>
                </label>
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Kata Sandi" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <InputLabel for="password_confirmation" value="Ulangi Kata Sandi" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ketik ulang kata sandi"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-2">
                <PrimaryButton class="w-full" :disabled="form.processing">
                    <span v-if="form.processing">Mendaftarkan...</span>
                    <span v-else>Daftar Sekarang</span>
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-slate-600 border-t border-slate-200 pt-4">
            Sudah memiliki akun?
            <Link href="/login" class="font-bold text-red-600 hover:text-red-700 underline ml-1">
                Masuk di sini
            </Link>
        </div>
    </GuestLayout>
</template>
