<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import InputLabel from '../../../components/InputLabel.vue';
import TextInput from '../../../components/TextInput.vue';
import InputError from '../../../components/InputError.vue';
import PrimaryButton from '../../../components/PrimaryButton.vue';

interface SettingsData {
    workshop_name: string;
    workshop_phone: string;
    workshop_address: string;
    daily_quota: number;
    operating_hours_start: string;
    operating_hours_end: string;
    mechanics_count: number;
    booking_window_days: number;
    booking_cutoff_time: string;
    cancellation_threshold_hours: number;
    late_tolerance_minutes: number;
    max_active_bookings_per_user: number;
    max_waiting_list_slots: number;
    no_show_threshold: number;
    block_duration_days: number;
    wa_template_booking_confirmed: string;
    wa_template_service_completed: string;
    wa_template_waiting_list_promoted: string;
    wa_template_service_reminder: string;
}

const props = defineProps<{
    settings: SettingsData;
}>();

const activeTab = ref<'profil' | 'operasional' | 'reservasi' | 'disiplin' | 'whatsapp'>('profil');

const form = useForm({
    ...props.settings,
});

const submit = () => {
    form.post('/admin/settings', {
        preserveScroll: true,
    });
};

// Helper untuk menyisipkan variabel ke template WhatsApp
const insertVariable = (field: keyof SettingsData, tag: string) => {
    if (typeof form[field] === 'string') {
        (form[field] as string) += ` ${tag} `;
    }
};

const placeholders = [
    { tag: '{nama}', desc: 'Nama pelanggan' },
    { tag: '{plat}', desc: 'Plat nomor motor' },
    { tag: '{layanan}', desc: 'Nama paket servis' },
    { tag: '{kode_booking}', desc: 'Kode booking' },
    { tag: '{jam_tutup}', desc: 'Jam operasional' },
    { tag: '{nama_bengkel}', desc: 'Nama bengkel' },
];
</script>

<template>
    <AppLayout title="Pengaturan Bengkel">
        <Head title="Pengaturan Bengkel - Admin ALDI-MOTOR" />

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Pengaturan Operasional & Aturan Bisnis</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Konfigurasi parameter kuota harian, jam kerja, kebijakan no-show, dan template notifikasi WhatsApp.
                    </p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-px">
                <button
                    type="button"
                    @click="activeTab = 'profil'"
                    class="px-4 py-2 text-xs font-bold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === 'profil' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    🏢 Profil Bengkel
                </button>
                <button
                    type="button"
                    @click="activeTab = 'operasional'"
                    class="px-4 py-2 text-xs font-bold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === 'operasional' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    ⚙️ Kuota & Jam Operasional
                </button>
                <button
                    type="button"
                    @click="activeTab = 'reservasi'"
                    class="px-4 py-2 text-xs font-bold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === 'reservasi' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    📅 Aturan Reservasi
                </button>
                <button
                    type="button"
                    @click="activeTab = 'disiplin'"
                    class="px-4 py-2 text-xs font-bold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === 'disiplin' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    🚫 No-Show & Blokir
                </button>
                <button
                    type="button"
                    @click="activeTab = 'whatsapp'"
                    class="px-4 py-2 text-xs font-bold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === 'whatsapp' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    💬 Template WhatsApp
                </button>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
                <!-- TAB 1: PROFIL BENGKEL -->
                <div v-show="activeTab === 'profil'" class="space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Identitas Bengkel</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="workshop_name" value="Nama Bengkel" />
                            <TextInput id="workshop_name" v-model="form.workshop_name" required />
                            <InputError :message="form.errors.workshop_name" />
                        </div>
                        <div>
                            <InputLabel for="workshop_phone" value="Nomor WhatsApp Resmi Bengkel" />
                            <TextInput id="workshop_phone" v-model="form.workshop_phone" required placeholder="6281234567890" />
                            <InputError :message="form.errors.workshop_phone" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="workshop_address" value="Alamat Lengkap Bengkel" />
                        <TextInput id="workshop_address" v-model="form.workshop_address" required />
                        <InputError :message="form.errors.workshop_address" />
                    </div>
                </div>

                <!-- TAB 2: KUOTA & JAM OPERASIONAL -->
                <div v-show="activeTab === 'operasional'" class="space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Kapasitas & Jam Kerja</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="daily_quota" value="Kuota Harian Standar (Motor / Hari)" />
                            <TextInput id="daily_quota" type="number" min="1" max="200" v-model="form.daily_quota" required />
                            <p class="text-[11px] text-slate-400 mt-1">Default 20 motor/hari. Dapat ditimpa per tanggal lewat menu Kapasitas.</p>
                            <InputError :message="form.errors.daily_quota" />
                        </div>
                        <div>
                            <InputLabel for="mechanics_count" value="Jumlah Mekanik Aktif" />
                            <TextInput id="mechanics_count" type="number" min="1" max="50" v-model="form.mechanics_count" required />
                            <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk menghitung beban kerja rata-rata per teknisi.</p>
                            <InputError :message="form.errors.mechanics_count" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="operating_hours_start" value="Jam Buka Operasional (WIB)" />
                            <TextInput id="operating_hours_start" type="time" v-model="form.operating_hours_start" required />
                            <InputError :message="form.errors.operating_hours_start" />
                        </div>
                        <div>
                            <InputLabel for="operating_hours_end" value="Jam Tutup Operasional (WIB)" />
                            <TextInput id="operating_hours_end" type="time" v-model="form.operating_hours_end" required />
                            <InputError :message="form.errors.operating_hours_end" />
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ATURAN RESERVASI -->
                <div v-show="activeTab === 'reservasi'" class="space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Kebijakan Pemesanan Slot</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="booking_window_days" value="Rentang Hari Booking Dibuka ke Depan (Hari)" />
                            <TextInput id="booking_window_days" type="number" min="1" max="90" v-model="form.booking_window_days" required />
                            <p class="text-[11px] text-slate-400 mt-1">Misal: 14 hari, pelanggan hanya bisa memesan slot hingga 14 hari ke depan.</p>
                            <InputError :message="form.errors.booking_window_days" />
                        </div>
                        <div>
                            <InputLabel for="booking_cutoff_time" value="Jam Batas Akhir Booking H-1 (WIB)" />
                            <TextInput id="booking_cutoff_time" type="time" v-model="form.booking_cutoff_time" required />
                            <p class="text-[11px] text-slate-400 mt-1">Pemesanan untuk besok ditutup setelah jam ini (misal 21:00).</p>
                            <InputError :message="form.errors.booking_cutoff_time" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="cancellation_threshold_hours" value="Batas Waktu Pembatalan Mandiri (Jam Sebelum Jadwal)" />
                            <TextInput id="cancellation_threshold_hours" type="number" min="0" max="48" v-model="form.cancellation_threshold_hours" required />
                            <p class="text-[11px] text-slate-400 mt-1">Contoh: 2 jam sebelum jadwal tiba.</p>
                            <InputError :message="form.errors.cancellation_threshold_hours" />
                        </div>
                        <div>
                            <InputLabel for="late_tolerance_minutes" value="Toleransi Keterlambatan Check-In (Menit)" />
                            <TextInput id="late_tolerance_minutes" type="number" min="0" max="120" v-model="form.late_tolerance_minutes" required />
                            <p class="text-[11px] text-slate-400 mt-1">Setelah lewat batas ini tanpa kabar, status otomatis dinyatakan No-Show.</p>
                            <InputError :message="form.errors.late_tolerance_minutes" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="max_active_bookings_per_user" value="Maksimal Booking Aktif per Akun per Hari" />
                            <TextInput id="max_active_bookings_per_user" type="number" min="1" max="10" v-model="form.max_active_bookings_per_user" required />
                            <InputError :message="form.errors.max_active_bookings_per_user" />
                        </div>
                        <div>
                            <InputLabel for="max_waiting_list_slots" value="Maksimal Slot Daftar Tunggu / Antrean Tambahan" />
                            <TextInput id="max_waiting_list_slots" type="number" min="0" max="50" v-model="form.max_waiting_list_slots" required />
                            <InputError :message="form.errors.max_waiting_list_slots" />
                        </div>
                    </div>
                </div>

                <!-- TAB 4: NO-SHOW & BLOKIR -->
                <div v-show="activeTab === 'disiplin'" class="space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Aturan Penegakan Disiplin Kedatangan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="no_show_threshold" value="Batas Ambang No-Show (Kali)" />
                            <TextInput id="no_show_threshold" type="number" min="1" max="10" v-model="form.no_show_threshold" required />
                            <p class="text-[11px] text-slate-400 mt-1">Jika pelanggan tidak hadir tanpa konfirmasi sebanyak ini, akun akan diblokir sementara.</p>
                            <InputError :message="form.errors.no_show_threshold" />
                        </div>
                        <div>
                            <InputLabel for="block_duration_days" value="Durasi Pemblokiran Akun (Hari)" />
                            <TextInput id="block_duration_days" type="number" min="1" max="90" v-model="form.block_duration_days" required />
                            <p class="text-[11px] text-slate-400 mt-1">Masa hukuman blokir sebelum akun dapat melakukan booking servis kembali.</p>
                            <InputError :message="form.errors.block_duration_days" />
                        </div>
                    </div>
                </div>

                <!-- TAB 5: TEMPLATE WHATSAPP -->
                <div v-show="activeTab === 'whatsapp'" class="space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900">Format Template Pesan WhatsApp</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Klik variabel di bawah untuk menyisipkan placeholder otomatis ke dalam isi pesan.
                        </p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span
                                v-for="p in placeholders"
                                :key="p.tag"
                                class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-mono border border-slate-200"
                            >
                                {{ p.tag }} = {{ p.desc }}
                            </span>
                        </div>
                    </div>

                    <!-- Template 1: Booking Dikonfirmasi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="wa_template_booking_confirmed" value="1. Pesan Konfirmasi Booking Sukses" />
                            <div class="flex gap-1 text-[10px]">
                                <button type="button" @click="insertVariable('wa_template_booking_confirmed', '{nama}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ nama</button>
                                <button type="button" @click="insertVariable('wa_template_booking_confirmed', '{kode_booking}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ kode</button>
                                <button type="button" @click="insertVariable('wa_template_booking_confirmed', '{plat}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ plat</button>
                            </div>
                        </div>
                        <textarea
                            id="wa_template_booking_confirmed"
                            v-model="form.wa_template_booking_confirmed"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-800 font-mono focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                        ></textarea>
                        <InputError :message="form.errors.wa_template_booking_confirmed" />
                    </div>

                    <!-- Template 2: Servis Selesai -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="wa_template_service_completed" value="2. Pesan Servis Selesai & Motor Siap Diambil" />
                            <div class="flex gap-1 text-[10px]">
                                <button type="button" @click="insertVariable('wa_template_service_completed', '{nama}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ nama</button>
                                <button type="button" @click="insertVariable('wa_template_service_completed', '{plat}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ plat</button>
                                <button type="button" @click="insertVariable('wa_template_service_completed', '{jam_tutup}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ jam_tutup</button>
                            </div>
                        </div>
                        <textarea
                            id="wa_template_service_completed"
                            v-model="form.wa_template_service_completed"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-800 font-mono focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                        ></textarea>
                        <InputError :message="form.errors.wa_template_service_completed" />
                    </div>

                    <!-- Template 3: Naik dari Waiting List -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="wa_template_waiting_list_promoted" value="3. Pesan Promosi Slot dari Daftar Tunggu" />
                            <div class="flex gap-1 text-[10px]">
                                <button type="button" @click="insertVariable('wa_template_waiting_list_promoted', '{nama}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ nama</button>
                                <button type="button" @click="insertVariable('wa_template_waiting_list_promoted', '{kode_booking}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ kode</button>
                            </div>
                        </div>
                        <textarea
                            id="wa_template_waiting_list_promoted"
                            v-model="form.wa_template_waiting_list_promoted"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-800 font-mono focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                        ></textarea>
                        <InputError :message="form.errors.wa_template_waiting_list_promoted" />
                    </div>

                    <!-- Template 4: Pengingat Servis Berkala -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="wa_template_service_reminder" value="4. Pesan Pengingat Servis Berkala (Periodic Reminder)" />
                            <div class="flex gap-1 text-[10px]">
                                <button type="button" @click="insertVariable('wa_template_service_reminder', '{nama}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ nama</button>
                                <button type="button" @click="insertVariable('wa_template_service_reminder', '{layanan}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ layanan</button>
                                <button type="button" @click="insertVariable('wa_template_service_reminder', '{plat}')" class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded">+ plat</button>
                            </div>
                        </div>
                        <textarea
                            id="wa_template_service_reminder"
                            v-model="form.wa_template_service_reminder"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-800 font-mono focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                        ></textarea>
                        <InputError :message="form.errors.wa_template_service_reminder" />
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <p class="text-[11px] text-slate-400">
                        * Perubahan pengaturan langsung diterapkan dan cache server otomatis disegarkan.
                    </p>
                    <PrimaryButton :disabled="form.processing">
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Simpan Seluruh Pengaturan</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
