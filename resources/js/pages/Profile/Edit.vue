<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import InputLabel from '../../components/InputLabel.vue';
import TextInput from '../../components/TextInput.vue';
import InputError from '../../components/InputError.vue';
import PrimaryButton from '../../components/PrimaryButton.vue';
import type { PageProps, User } from '../../types';

const page = usePage<PageProps>();
const user = computed<User>(() => page.props.auth.user as User);

// Form update profil
const form = useForm({
    name: user.value.name,
    email: user.value.email,
    phone: user.value.phone,
    whatsapp_opt_in: user.value.whatsapp_opt_in,
});

const submit = () => {
    form.patch('/profile', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Pengaturan Profil">
        <Head title="Profil Saya - ALDI-MOTOR" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header Profil -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Pengaturan Akun</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Kelola data pribadi Anda, nomor WhatsApp aktif, dan preferensi notifikasi.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                    <span>Peran:</span>
                    <span class="font-bold uppercase text-red-600">{{ user.role_label }}</span>
                </div>
            </div>

            <!-- Form Ubah Data Profil -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 mb-1">Informasi Profil</h2>
                <p class="text-xs text-slate-500 mb-6">
                    Pembaruan nomor WhatsApp akan digunakan oleh staf bengkel untuk mengirimkan estimasi servis.
                </p>

                <form @submit.prevent="submit" class="space-y-5 max-w-xl">
                    <!-- Nama -->
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autocomplete="name"
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
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div>
                        <InputLabel for="phone" value="Nomor WhatsApp" />
                        <TextInput
                            id="phone"
                            type="tel"
                            v-model="form.phone"
                            required
                            placeholder="Contoh: 081234567890"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Disimpan dalam format internasional standar (628xxx).
                        </p>
                        <InputError :message="form.errors.phone" />
                    </div>

                    <!-- Checkbox WhatsApp Opt-in -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.whatsapp_opt_in"
                                class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500"
                            />
                            <div class="text-xs text-slate-700 leading-snug">
                                <span class="font-semibold block text-slate-900 mb-0.5">Terima Notifikasi WhatsApp</span>
                                Izinkan bengkel mengirimkan tautan estimasi biaya, progres pengerjaan, dan pengingat servis otomatis via WhatsApp.
                            </div>
                        </label>
                    </div>

                    <!-- Tombol Simpan -->
                    <div class="flex items-center gap-4 pt-2">
                        <PrimaryButton :disabled="form.processing">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </PrimaryButton>

                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0 translate-y-1"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-xs text-emerald-600 font-semibold">
                                ✓ Berhasil disimpan.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
