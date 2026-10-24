<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import InputLabel from '../../../components/InputLabel.vue';
import TextInput from '../../../components/TextInput.vue';
import InputError from '../../../components/InputError.vue';
import PrimaryButton from '../../../components/PrimaryButton.vue';

interface AffectedBooking {
    id: number;
    booking_code: string;
    customer_name: string;
    customer_phone: string | null;
    vehicle: string;
    service_name: string;
    status: string;
    status_label: string;
    estimated_arrival_at: string;
}

interface CalendarDay {
    date: string;
    day_number: number;
    day_name: string;
    is_today: boolean;
    is_past: boolean;
    is_open: boolean;
    quota: number;
    has_override: boolean;
    override_quota: number | null;
    note: string | null;
    active_bookings_count: number;
    remaining_slots: number;
    active_bookings: AffectedBooking[];
}

const props = defineProps<{
    month: string;
    month_label: string;
    prev_month: string;
    next_month: string;
    default_quota: number;
    days: CalendarDay[];
    summary: {
        total_days: number;
        open_days: number;
        closed_days: number;
        total_active_bookings: number;
    };
}>();

// Modal State
const showModal = ref(false);
const selectedDay = ref<CalendarDay | null>(null);

const form = useForm({
    date: '',
    is_open: true,
    quota: null as number | null,
    note: '',
    force_close: false,
});

const openEditModal = (day: CalendarDay) => {
    selectedDay.value = day;
    form.clearErrors();
    form.date = day.date;
    form.is_open = day.is_open;
    form.quota = day.override_quota;
    form.note = day.note || '';
    form.force_close = false;
    showModal.value = true;
};

const submitForm = () => {
    form.post('/admin/capacities', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            selectedDay.value = null;
        },
    });
};

// Cek apakah penutupan tanggal ditolak sementara karena belum mencentang konfirmasi darurat
const isClosingBlockedByBookings = computed(() => {
    if (!selectedDay.value) return false;
    return !form.is_open && selectedDay.value.active_bookings_count > 0 && !form.force_close;
});
</script>

<template>
    <AppLayout title="Kalender Kapasitas & Kuota Bengkel">
        <Head title="Kalender Kapasitas - Admin ALDI-MOTOR" />

        <div class="space-y-6">
            <!-- Header Section with Month Navigation -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Kalender Kapasitas Operasional</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Atur kuota harian khusus, tandai hari libur bengkel, dan pantau kepadatan jadwal servis motor per tanggal.
                    </p>
                </div>

                <!-- Navigasi Bulan -->
                <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 p-1.5 rounded-2xl">
                    <Link
                        :href="`/admin/capacities?month=${prev_month}`"
                        class="px-3 py-1.5 text-xs font-bold rounded-xl hover:bg-white text-slate-700 transition"
                    >
                        &larr; Bulan Lalu
                    </Link>

                    <span class="px-4 py-1.5 text-xs font-black text-slate-900 bg-white shadow-xs rounded-xl border border-slate-200 uppercase tracking-wider">
                        📅 {{ month_label }}
                    </span>

                    <Link
                        :href="`/admin/capacities?month=${next_month}`"
                        class="px-3 py-1.5 text-xs font-bold rounded-xl hover:bg-white text-slate-700 transition"
                    >
                        Bulan Depan &rarr;
                    </Link>
                </div>
            </div>

            <!-- Ringkasan Statistik Bulanan -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Hari Operasional</span>
                    <span class="text-xl font-black text-emerald-600">{{ summary.open_days }} Hari</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Hari Libur / Tutup</span>
                    <span class="text-xl font-black text-rose-600">{{ summary.closed_days }} Hari</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total Booking Bulan Ini</span>
                    <span class="text-xl font-black text-slate-900">{{ summary.total_active_bookings }} Motor</span>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Kuota Standar Sistem</span>
                    <span class="text-xl font-black text-slate-700">{{ default_quota }} Slot / Hari</span>
                </div>
            </div>

            <!-- Grid Kalender -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
                    <div
                        v-for="day in days"
                        :key="day.date"
                        @click="openEditModal(day)"
                        class="p-3.5 rounded-2xl border transition cursor-pointer flex flex-col justify-between min-h-[120px] relative group"
                        :class="[
                            day.is_today ? 'ring-2 ring-red-500 border-red-400 bg-red-50/20' : '',
                            !day.is_open ? 'bg-slate-100 border-slate-200 opacity-80' : 'bg-white hover:border-slate-400 hover:shadow-xs border-slate-200',
                            day.is_past ? 'opacity-70' : ''
                        ]"
                    >
                        <!-- Baris Atas: Tanggal & Nama Hari -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-black" :class="day.is_today ? 'text-red-600' : 'text-slate-800'">
                                {{ day.day_number }}
                            </span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">
                                {{ day.day_name }}
                            </span>
                        </div>

                        <!-- Bagian Tengah: Status Buka / Kuota -->
                        <div class="my-2 text-xs">
                            <template v-if="!day.is_open">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                    ⛔ Libur / Tutup
                                </span>
                                <p v-if="day.note" class="text-[10px] text-slate-500 line-clamp-1 mt-1 italic">
                                    {{ day.note }}
                                </p>
                            </template>
                            <template v-else>
                                <div class="flex items-center justify-between text-[11px] font-semibold mb-1">
                                    <span class="text-slate-600">{{ day.active_bookings_count }} / {{ day.quota }} Slot</span>
                                    <span
                                        class="font-bold text-[10px]"
                                        :class="day.remaining_slots === 0 ? 'text-rose-600' : (day.remaining_slots <= 3 ? 'text-amber-600' : 'text-emerald-600')"
                                    >
                                        {{ day.remaining_slots === 0 ? 'Penuh' : `Sisa ${day.remaining_slots}` }}
                                    </span>
                                </div>
                                <!-- Progress Bar Keterisian -->
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="day.remaining_slots === 0 ? 'bg-rose-500' : (day.remaining_slots <= 3 ? 'bg-amber-500' : 'bg-emerald-500')"
                                        :style="{ width: `${Math.min(100, (day.active_bookings_count / day.quota) * 100)}%` }"
                                    ></div>
                                </div>
                                <span v-if="day.has_override && day.override_quota !== null" class="text-[9px] text-purple-600 font-bold block mt-1">
                                    ★ Kuota Khusus
                                </span>
                            </template>
                        </div>

                        <!-- Baris Bawah: Hint Edit -->
                        <div class="text-[9px] text-slate-400 flex items-center justify-between border-t border-slate-100 pt-1">
                            <span>{{ day.is_today ? 'Hari Ini' : '' }}</span>
                            <span class="group-hover:text-red-600 font-semibold transition">Ubah &rarr;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL KELOLA KAPASITAS & LIBUR -->
        <div v-if="showModal && selectedDay" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-base text-slate-900">
                            Atur Kapasitas: {{ selectedDay.day_name }}, {{ selectedDay.date }}
                        </h3>
                        <p class="text-[11px] text-slate-400">Konfigurasi kuota dan status operasional hari ini.</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <!-- Status Buka / Tutup -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800 block">Status Operasional Bengkel</span>
                            <span class="text-[11px] text-slate-500">Tutup operasional jika ada hari libur nasional atau perbaikan bengkel.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_open" class="sr-only peer" />
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>
                    <InputError :message="form.errors.is_open" />

                    <!-- Override Kuota -->
                    <div v-if="form.is_open">
                        <InputLabel for="quota" value="Override Kuota Harian (Motor)" />
                        <TextInput
                            id="quota"
                            type="number"
                            min="1"
                            max="200"
                            v-model="form.quota"
                            :placeholder="`Default sistem: ${default_quota} motor`"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Kosongkan jika ingin mengikuti kuota standar bengkel ({{ default_quota }} slot).
                        </p>
                        <InputError :message="form.errors.quota" />
                    </div>

                    <!-- Catatan Operasional -->
                    <div>
                        <InputLabel for="note" value="Catatan / Keterangan Operasional" />
                        <TextInput
                            id="note"
                            v-model="form.note"
                            placeholder="Contoh: Buka setengah hari / Libur Cuti Bersama"
                        />
                        <InputError :message="form.errors.note" />
                    </div>

                    <!-- PERINGATAN KRUSIAL: JIKA ADA BOOKING AKTIF DAN INGIN MENUTUP TANGGAL -->
                    <div
                        v-if="!form.is_open && selectedDay.active_bookings_count > 0"
                        class="p-4 rounded-xl border border-rose-300 bg-rose-50 text-rose-900 space-y-3"
                    >
                        <div class="flex items-start gap-2">
                            <span class="text-lg">⚠️</span>
                            <div>
                                <h4 class="font-bold text-xs text-rose-900">
                                    Peringatan: Ada {{ selectedDay.active_bookings_count }} Booking Aktif!
                                </h4>
                                <p class="text-[11px] text-rose-700 mt-0.5">
                                    Menutup tanggal ini akan berdampak pada jadwal pelanggan berikut yang telah memiliki nomor antrean resmi:
                                </p>
                            </div>
                        </div>

                        <!-- Daftar Booking Terdampak -->
                        <div class="max-h-36 overflow-y-auto rounded-lg bg-white/80 border border-rose-200 divide-y divide-rose-100 text-[11px]">
                            <div
                                v-for="b in selectedDay.active_bookings"
                                :key="b.id"
                                class="p-2 flex items-center justify-between"
                            >
                                <div>
                                    <span class="font-bold text-slate-900">{{ b.booking_code }}</span> &bull; {{ b.customer_name }}
                                    <div class="text-[10px] text-slate-500 font-mono">{{ b.vehicle }} - {{ b.service_name }}</div>
                                </div>
                                <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold text-[10px]">
                                    {{ b.estimated_arrival_at }}
                                </span>
                            </div>
                        </div>

                        <label class="flex items-start gap-2 cursor-pointer pt-1">
                            <input
                                type="checkbox"
                                v-model="form.force_close"
                                class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500"
                            />
                            <span class="text-[11px] text-rose-800 font-semibold leading-tight">
                                Saya memahami risiko dan menyetujui penutupan operasional darurat pada tanggal ini.
                            </span>
                        </label>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <PrimaryButton :disabled="form.processing || isClosingBlockedByBookings">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
