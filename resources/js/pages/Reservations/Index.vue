<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import type { BookingSlot, PageProps, ServiceItem, User, Vehicle } from '../../types';

interface Props {
    selected_date: string;
    min_date: string;
    max_date: string;
    is_open: boolean;
    is_cutoff_passed: boolean;
    note: string | null;
    daily_quota: number;
    total_booked: number;
    slots: BookingSlot[];
    services: ServiceItem[];
    vehicles: Vehicle[];
    workshop_info: {
        name: string;
        phone: string;
        address: string;
        start_time: string;
        end_time: string;
    };
}

const props = defineProps<Props>();
const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);

// State mode input kendaraan: 'existing' atau 'new'
const vehicleMode = ref<'existing' | 'new'>(props.vehicles && props.vehicles.length > 0 ? 'existing' : 'new');
const isRestoredDraft = ref(false);

// Form data reservasi
const form = useForm({
    booking_date: props.selected_date,
    estimated_arrival_at: '',
    service_id: props.services.length > 0 ? props.services[0].id : null,
    vehicle_id: props.vehicles.length > 0 ? props.vehicles[0].id : null,
    plate_number: '',
    brand: '',
    model: '',
    year: new Date().getFullYear(),
    complaint: '',
});

// Pilih slot jam kedatangan
const selectSlot = (slot: BookingSlot) => {
    if (slot.status === 'penuh' || !props.is_open || props.is_cutoff_passed) {
        return;
    }
    form.estimated_arrival_at = slot.time;
};

// Ganti tanggal dengan router.get preserveState, preserveScroll, only agar responsif dan cepat
const onDateChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const newDate = target.value;
    if (!newDate) return;

    form.booking_date = newDate;
    form.estimated_arrival_at = ''; // reset slot terpilih saat tanggal berganti

    router.get(
        '/reservasi',
        { tanggal: newDate },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['selected_date', 'slots', 'is_open', 'note', 'daily_quota', 'total_booked', 'is_cutoff_passed'],
        }
    );
};

// Navigasi cepat H+1, H+2
const setRelativeDate = (offsetDays: number) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const dateStr = d.toISOString().split('T')[0];

    form.booking_date = dateStr;
    form.estimated_arrival_at = '';

    router.get(
        '/reservasi',
        { tanggal: dateStr },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['selected_date', 'slots', 'is_open', 'note', 'daily_quota', 'total_booked', 'is_cutoff_passed'],
        }
    );
};

// Simpan draft ke sessionStorage jika belum login (Konsep Sederhana Inertia)
const saveDraftAndLogin = () => {
    const draft = {
        booking_date: form.booking_date,
        estimated_arrival_at: form.estimated_arrival_at,
        service_id: form.service_id,
        vehicle_id: form.vehicle_id,
        plate_number: form.plate_number,
        brand: form.brand,
        model: form.model,
        year: form.year,
        complaint: form.complaint,
        vehicleMode: vehicleMode.value,
    };

    sessionStorage.setItem('aldi_motor_draft_booking', JSON.stringify(draft));
    router.visit('/login?redirect=/reservasi');
};

// Pulihkan draft saat halaman dimuat jika ditemukan di sessionStorage
onMounted(() => {
    const saved = sessionStorage.getItem('aldi_motor_draft_booking');
    if (saved) {
        try {
            const draft = JSON.parse(saved);
            if (draft.booking_date) form.booking_date = draft.booking_date;
            if (draft.estimated_arrival_at) form.estimated_arrival_at = draft.estimated_arrival_at;
            if (draft.service_id) form.service_id = draft.service_id;
            if (draft.complaint) form.complaint = draft.complaint;
            if (draft.plate_number) form.plate_number = draft.plate_number;
            if (draft.brand) form.brand = draft.brand;
            if (draft.model) form.model = draft.model;
            if (draft.year) form.year = draft.year;
            if (draft.vehicleMode) vehicleMode.value = draft.vehicleMode;

            if (user.value && props.vehicles.length > 0 && draft.vehicle_id) {
                form.vehicle_id = draft.vehicle_id;
            }

            isRestoredDraft.value = true;
            sessionStorage.removeItem('aldi_motor_draft_booking');
        } catch {
            // Abaikan parsing error
        }
    }
});

// Format mata uang Rupiah
const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

// Submit reservasi
const submitBooking = () => {
    if (!user.value) {
        saveDraftAndLogin();
        return;
    }

    form.post('/reservasi', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Reservasi Servis Motor">
        <Head title="Reservasi Servis Motor - ALDI-MOTOR" />

        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Header Hero Banner -->
            <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider mb-2 backdrop-blur">
                            <span>⚡ Reservasi Online Cepat & Praktis</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Atur Jadwal Servis Motor Anda
                        </h1>
                        <p class="text-red-100 text-sm mt-1 max-w-xl">
                            Pilih tanggal dan slot jam kedatangan tanpa antre lama. Bengkel resmi UMKM <strong>{{ workshop_info.name }}</strong> siap merawat motor Anda.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 border border-white/20 shrink-0 text-right">
                        <div class="text-[11px] text-red-100 font-semibold uppercase tracking-wider">Jam Operasional</div>
                        <div class="text-base font-extrabold text-white mt-0.5">
                            {{ workshop_info.start_time }} - {{ workshop_info.end_time }} WIB
                        </div>
                        <div class="text-xs text-red-200 mt-1">
                            WhatsApp: <span class="font-mono">{{ workshop_info.phone }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner Notifikasi Pemulihan Draft -->
            <div v-if="isRestoredDraft" class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✨</span>
                    <div>
                        <div class="text-xs font-bold">Draft Pilihan Anda Berhasil Dipulihkan!</div>
                        <div class="text-xs text-emerald-700">Silakan pastikan data motor Anda dan klik "Konfirmasi Reservasi Servis" di bawah.</div>
                    </div>
                </div>
                <button @click="isRestoredDraft = false" class="text-xs font-bold text-emerald-600 hover:text-emerald-800">
                    ✕ Tutup
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Kolom Kiri: Pemilih Tanggal & Slot Jam -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Pemilih Tanggal -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">1</span>
                                    Pilih Tanggal Kedatangan
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Reservasi dibuka hingga {{ max_date }}.</p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="setRelativeDate(0)"
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg transition"
                                    :class="selected_date === min_date ? 'bg-red-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                                >
                                    Hari Ini
                                </button>
                                <button
                                    type="button"
                                    @click="setRelativeDate(1)"
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg transition bg-slate-100 hover:bg-slate-200 text-slate-700"
                                >
                                    Besok
                                </button>
                                <button
                                    type="button"
                                    @click="setRelativeDate(2)"
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg transition bg-slate-100 hover:bg-slate-200 text-slate-700"
                                >
                                    Lusa
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="w-full sm:w-64">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Kalender Tanggal
                                </label>
                                <input
                                    type="date"
                                    :value="selected_date"
                                    :min="min_date"
                                    :max="max_date"
                                    @change="onDateChange"
                                    class="w-full text-sm font-semibold rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                                />
                            </div>

                            <div class="flex-1 bg-slate-50 rounded-xl p-3 border border-slate-200/80 flex items-center justify-between text-xs">
                                <div>
                                    <div class="text-slate-500 font-medium">Status Operasional:</div>
                                    <div class="font-bold text-slate-800 flex items-center gap-1.5 mt-0.5">
                                        <span class="w-2 h-2 rounded-full" :class="is_open ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ is_open ? 'Bengkel Buka Melayani Servis' : 'Bengkel Libur / Tutup' }}
                                        <span v-if="note" class="text-slate-500 font-normal">({{ note }})</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-slate-500 font-medium">Kapasitas Hari Ini:</div>
                                    <div class="font-bold text-slate-800">
                                        {{ total_booked }} / {{ daily_quota }} Antrean
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peringatan jika tanggal libur atau cutoff lewat -->
                        <div v-if="!is_open" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                            <span>⛔</span>
                            <span>Mohon maaf, bengkel libur pada tanggal ini{{ note ? `: ${note}` : '' }}. Silakan pilih tanggal operasional lain.</span>
                        </div>
                        <div v-else-if="is_cutoff_passed" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold flex items-center gap-2">
                            <span>⏰</span>
                            <span>Batas waktu pendaftaran booking untuk hari ini sudah ditutup. Silakan pilih jadwal untuk besok atau hari berikutnya.</span>
                        </div>
                    </div>

                    <!-- 2. Pemilih Slot Jam Kedatangan -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">2</span>
                                    Pilih Slot Jam Kedatangan
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Warna menunjukkan sisa kuota kedatangan pada jam tersebut.</p>
                            </div>

                            <!-- Legend Status -->
                            <div class="hidden sm:flex items-center gap-3 text-[11px] font-semibold">
                                <span class="flex items-center gap-1 text-emerald-700">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Tersedia
                                </span>
                                <span class="flex items-center gap-1 text-amber-700">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Hampir Penuh
                                </span>
                                <span class="flex items-center gap-1 text-slate-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span> Penuh
                                </span>
                            </div>
                        </div>

                        <!-- Grid Slot Waktu -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <button
                                v-for="slot in slots"
                                :key="slot.time"
                                type="button"
                                @click="selectSlot(slot)"
                                :disabled="slot.status === 'penuh' || !is_open || is_cutoff_passed"
                                class="relative p-3 rounded-xl border text-left transition flex flex-col justify-between group"
                                :class="[
                                    form.estimated_arrival_at === slot.time
                                        ? 'border-red-600 bg-red-50/70 ring-2 ring-red-500 shadow-xs'
                                        : slot.status === 'tersedia'
                                            ? 'border-emerald-200 bg-emerald-50/40 hover:border-emerald-400 text-slate-800'
                                            : slot.status === 'hampir penuh'
                                                ? 'border-amber-200 bg-amber-50/50 hover:border-amber-400 text-slate-800'
                                                : 'border-slate-200 bg-slate-100/70 text-slate-400 opacity-60 cursor-not-allowed'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-extrabold text-sm font-mono tracking-tight"
                                          :class="form.estimated_arrival_at === slot.time ? 'text-red-700' : 'text-slate-900'">
                                        {{ slot.label }}
                                    </span>
                                    <span v-if="form.estimated_arrival_at === slot.time" class="text-xs text-red-600 font-bold">✓</span>
                                </div>

                                <div class="mt-2 flex items-center justify-between text-[11px]">
                                    <span class="font-semibold"
                                          :class="slot.status === 'tersedia' ? 'text-emerald-700' : slot.status === 'hampir penuh' ? 'text-amber-700' : 'text-slate-400'">
                                        {{ slot.status === 'tersedia' ? `Sisa ${slot.remaining} slot` : slot.status === 'hampir penuh' ? `Sisa 1 slot!` : 'Penuh' }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ slot.booked }}/{{ slot.capacity }}
                                    </span>
                                </div>
                            </button>
                        </div>
                        <div v-if="form.errors.estimated_arrival_at" class="text-xs text-rose-600 font-semibold">
                            {{ form.errors.estimated_arrival_at }}
                        </div>
                    </div>

                    <!-- 3. Pilih Layanan Servis -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-4">
                            <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">3</span>
                                Pilih Paket Layanan Servis
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Pilih jenis servis motor yang Anda butuhkan.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div
                                v-for="service in services"
                                :key="service.id"
                                @click="form.service_id = service.id"
                                class="p-4 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                :class="form.service_id === service.id
                                    ? 'border-red-600 bg-red-50/60 ring-2 ring-red-500'
                                    : 'border-slate-200 hover:border-slate-300 bg-white'"
                            >
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-extrabold text-sm text-slate-900">{{ service.name }}</h4>
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[10px] font-bold text-slate-600 shrink-0">
                                            ⏱️ {{ service.duration_minutes }} mnt
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ service.description || 'Pemeriksaan dan servis motor standar.' }}</p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                    <span class="text-slate-400 font-medium">Estimasi Biaya:</span>
                                    <span class="font-extrabold text-slate-900 font-mono text-sm">
                                        {{ formatRupiah(service.price_estimate) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.service_id" class="text-xs text-rose-600 font-semibold">
                            {{ form.errors.service_id }}
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Data Kendaraan & Konfirmasi Booking -->
                <div class="space-y-6">
                    <!-- 4. Pilih / Tambah Kendaraan -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">4</span>
                                Data Motor
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Motor yang akan diservis di bengkel.</p>
                        </div>

                        <!-- Tab opsi jika user login & punya motor terdaftar -->
                        <div v-if="user && vehicles.length > 0" class="flex p-1 bg-slate-100 rounded-xl">
                            <button
                                type="button"
                                @click="vehicleMode = 'existing'"
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="vehicleMode === 'existing' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'"
                            >
                                Dari Garasi ({{ vehicles.length }})
                            </button>
                            <button
                                type="button"
                                @click="vehicleMode = 'new'"
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="vehicleMode === 'new' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'"
                            >
                                + Motor Baru
                            </button>
                        </div>

                        <!-- Mode 1: Pilih dari garasi -->
                        <div v-if="user && vehicleMode === 'existing' && vehicles.length > 0" class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Pilih Motor Terdaftar
                            </label>
                            <div class="space-y-2">
                                <label
                                    v-for="v in vehicles"
                                    :key="v.id"
                                    class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition text-xs"
                                    :class="form.vehicle_id === v.id ? 'border-red-600 bg-red-50/50 ring-1 ring-red-500' : 'border-slate-200 hover:bg-slate-50'"
                                >
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            name="selected_vehicle"
                                            :value="v.id"
                                            v-model="form.vehicle_id"
                                            class="text-red-600 focus:ring-red-500"
                                        />
                                        <div>
                                            <div class="font-extrabold text-slate-900 font-mono">{{ v.plate_number }}</div>
                                            <div class="text-slate-500">{{ v.brand }} {{ v.model }} ({{ v.year }})</div>
                                        </div>
                                    </div>
                                    <span class="text-slate-400">🛵</span>
                                </label>
                            </div>
                        </div>

                        <!-- Mode 2: Tambah Kendaraan Baru (atau untuk Tamu) -->
                        <div v-else class="space-y-3">
                            <div v-if="!user" class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-medium">
                                💡 Data motor akan otomatis tersimpan ke akun Anda setelah Anda login/registrasi.
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nomor Plat Motor <span class="text-red-600">*</span>
                                </label>
                                <input
                                    v-model="form.plate_number"
                                    type="text"
                                    placeholder="Contoh: B 1234 XYZ"
                                    class="w-full text-xs font-bold uppercase rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5 font-mono"
                                />
                                <div v-if="form.errors.plate_number" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.plate_number }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Merk <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        v-model="form.brand"
                                        type="text"
                                        placeholder="Honda / Yamaha"
                                        class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                                    />
                                    <div v-if="form.errors.brand" class="text-xs text-rose-600 font-semibold mt-1">
                                        {{ form.errors.brand }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Tipe/Model <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        v-model="form.model"
                                        type="text"
                                        placeholder="Vario 160"
                                        class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                                    />
                                    <div v-if="form.errors.model" class="text-xs text-rose-600 font-semibold mt-1">
                                        {{ form.errors.model }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tahun Perakitan
                                </label>
                                <input
                                    v-model.number="form.year"
                                    type="number"
                                    min="1990"
                                    :max="new Date().getFullYear() + 1"
                                    class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                                />
                            </div>
                        </div>

                        <!-- Catatan Keluhan -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Keluhan / Catatan Kendala (Opsional)
                            </label>
                            <textarea
                                v-model="form.complaint"
                                rows="3"
                                placeholder="Ceritakan kendala pada motor Anda, misal: tarikan brebet, rem depan berdecit..."
                                class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500"
                            ></textarea>
                            <div v-if="form.errors.complaint" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ form.errors.complaint }}
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan & Tombol Aksi -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-3">
                            Ringkasan Reservasi
                        </h3>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tanggal Servis:</span>
                                <span class="font-bold text-slate-900">{{ form.booking_date }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Slot Jam Kedatangan:</span>
                                <span class="font-bold text-slate-900 font-mono" :class="form.estimated_arrival_at ? 'text-red-600' : 'text-slate-400'">
                                    {{ form.estimated_arrival_at ? `${form.estimated_arrival_at} WIB` : 'Belum dipilih' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Layanan:</span>
                                <span class="font-bold text-slate-900">
                                    {{ services.find(s => s.id === form.service_id)?.name || '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-slate-100 text-sm">
                                <span class="font-bold text-slate-700">Estimasi Biaya:</span>
                                <span class="font-extrabold text-red-600 font-mono">
                                    {{ formatRupiah(services.find(s => s.id === form.service_id)?.price_estimate || 0) }}
                                </span>
                            </div>
                        </div>

                        <div v-if="form.errors.booking_date" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs font-semibold">
                            {{ form.errors.booking_date }}
                        </div>

                        <!-- Tombol Konfirmasi Reservasi -->
                        <div class="pt-2">
                            <template v-if="user">
                                <button
                                    type="button"
                                    @click="submitBooking"
                                    :disabled="form.processing || !form.estimated_arrival_at || !is_open || is_cutoff_passed"
                                    class="w-full py-3.5 px-4 rounded-xl font-black text-sm text-white transition shadow-md flex items-center justify-center gap-2"
                                    :class="[
                                        form.processing || !form.estimated_arrival_at || !is_open || is_cutoff_passed
                                            ? 'bg-slate-300 cursor-not-allowed shadow-none'
                                            : 'bg-red-600 hover:bg-red-700 shadow-red-500/20 active:scale-[0.99]'
                                    ]"
                                >
                                    <span v-if="form.processing">Memproses Reservasi...</span>
                                    <span v-else-if="!form.estimated_arrival_at">Pilih Slot Jam Dahulu</span>
                                    <span v-else>Konfirmasi Reservasi Servis 🚀</span>
                                </button>
                            </template>

                            <template v-else>
                                <button
                                    type="button"
                                    @click="saveDraftAndLogin"
                                    :disabled="!form.estimated_arrival_at || !is_open || is_cutoff_passed"
                                    class="w-full py-3.5 px-4 rounded-xl font-black text-sm text-white bg-slate-900 hover:bg-slate-800 transition shadow-md flex items-center justify-center gap-2"
                                    :class="[!form.estimated_arrival_at || !is_open || is_cutoff_passed ? 'bg-slate-300 cursor-not-allowed shadow-none' : '']"
                                >
                                    <span>🔐 Masuk untuk Menyelesaikan Reservasi</span>
                                </button>
                                <p class="text-[11px] text-slate-500 text-center mt-2">
                                    Pilihan Anda akan disimpan otomatis dan dikembalikan setelah login.
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
