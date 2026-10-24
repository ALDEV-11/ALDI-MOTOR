<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { BookingItem } from '../../../types';

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface Props {
    bookings: Pagination<BookingItem>;
    counts: {
        all: number;
        active: number;
        completed: number;
        cancelled: number;
    };
    current_filter: string;
    cancellation_threshold_hours: number;
}

const props = defineProps<Props>();

// Modal pembatalan booking
const isCancelModalOpen = ref(false);
const selectedBooking = ref<BookingItem | null>(null);

const cancelForm = useForm({
    cancel_reason: '',
});

const openCancelModal = (booking: BookingItem) => {
    selectedBooking.value = booking;
    cancelForm.reset();
    cancelForm.clearErrors();
    isCancelModalOpen.value = true;
};

const closeCancelModal = () => {
    isCancelModalOpen.value = false;
    selectedBooking.value = null;
};

const submitCancellation = () => {
    if (!selectedBooking.value) return;

    cancelForm.post(`/beranda/bookings/${selectedBooking.value.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
        },
    });
};
</script>

<template>
    <AppLayout title="Booking Saya">
        <Head title="Booking Saya - ALDI-MOTOR" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        📋 Riwayat & Booking Saya
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau status antrean servis motor dan kelola reservasi Anda di ALDI-MOTOR.
                    </p>
                </div>

                <Link
                    href="/reservasi"
                    class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-md shadow-red-500/20 inline-flex items-center gap-1.5 self-start sm:self-auto"
                >
                    <span>+ Buat Reservasi Baru</span>
                </Link>
            </div>

            <!-- Tab Filter Status -->
            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <Link
                    href="/beranda/bookings"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                    :class="current_filter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                >
                    Semua ({{ counts.all }})
                </Link>
                <Link
                    href="/beranda/bookings?status=active"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                    :class="current_filter === 'active' ? 'bg-red-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                >
                    Antrean Aktif ({{ counts.active }})
                </Link>
                <Link
                    href="/beranda/bookings?status=completed"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                    :class="current_filter === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                >
                    Selesai ({{ counts.completed }})
                </Link>
                <Link
                    href="/beranda/bookings?status=cancelled"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                    :class="current_filter === 'cancelled' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                >
                    Dibatalkan ({{ counts.cancelled }})
                </Link>
            </div>

            <!-- List Booking Cards -->
            <div v-if="bookings.data.length > 0" class="space-y-4">
                <div
                    v-for="booking in bookings.data"
                    :key="booking.id"
                    class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition space-y-4"
                >
                    <!-- Baris Atas: Kode, Status, & Tanggal -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="font-extrabold text-slate-900 font-mono text-sm tracking-tight">
                                {{ booking.booking_code }}
                            </span>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border"
                                :class="booking.status_color"
                            >
                                {{ booking.status_label }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-500 flex items-center gap-2">
                            <span>📅 {{ booking.booking_date }}</span>
                            <span>•</span>
                            <span class="font-mono font-bold text-slate-700">⏰ {{ booking.estimated_arrival_at }}</span>
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">
                                Antrean #{{ booking.queue_order }}
                            </span>
                        </div>
                    </div>

                    <!-- Baris Tengah: Info Motor & Layanan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium">Kendaraan:</span>
                            <div class="font-bold text-slate-800 mt-0.5">
                                {{ booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model}` : 'Kendaraan Umum' }}
                            </div>
                            <div class="font-mono text-slate-500 font-bold">
                                {{ booking.vehicle?.plate_number || '-' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 font-medium">Layanan Servis:</span>
                            <div class="font-bold text-slate-800 mt-0.5">
                                {{ booking.service?.name || 'Paket Servis' }}
                            </div>
                            <div class="text-slate-500">
                                Estimasi waktu: {{ booking.service?.duration_minutes || 45 }} menit
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 font-medium">Catatan / Kendala:</span>
                            <div class="text-slate-700 mt-0.5 italic line-clamp-2">
                                "{{ booking.complaint || 'Tidak ada catatan keluhan khusus.' }}"
                            </div>
                        </div>
                    </div>

                    <!-- Baris Bawah: Tombol Aksi -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <div>
                            <span v-if="booking.status === 'cancelled' && booking.cancel_reason" class="text-[11px] text-rose-600 italic">
                                Alasan batal: {{ booking.cancel_reason }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Tombol Batal Booking (hanya status confirmed) -->
                            <button
                                v-if="booking.status === 'confirmed'"
                                type="button"
                                @click="openCancelModal(booking)"
                                class="px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold transition"
                            >
                                ✕ Batalkan Booking
                            </button>

                            <!-- Tombol Detail -->
                            <Link
                                :href="`/beranda/bookings/${booking.id}`"
                                class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs"
                            >
                                Lihat Detail & Riwayat →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Paginasi -->
                <div v-if="bookings.last_page > 1" class="flex justify-between items-center pt-4 text-xs text-slate-600">
                    <div>
                        Menampilkan halaman <strong>{{ bookings.current_page }}</strong> dari <strong>{{ bookings.last_page }}</strong>
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-if="bookings.prev_page_url"
                            :href="bookings.prev_page_url"
                            class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 font-bold"
                        >
                            ← Sebelumnya
                        </Link>
                        <Link
                            v-if="bookings.next_page_url"
                            :href="bookings.next_page_url"
                            class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 font-bold"
                        >
                            Berikutnya →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto mb-4">
                    🛵
                </div>
                <h3 class="text-base font-bold text-slate-900">Belum Ada Pemesanan</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                    Anda belum memiliki reservasi servis motor{{ current_filter !== 'all' ? ' pada kategori ini' : '' }}.
                </p>
                <Link
                    href="/reservasi"
                    class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-md shadow-red-500/20"
                >
                    + Buat Reservasi Sekarang
                </Link>
            </div>
        </div>

        <!-- Modal Konfirmasi Pembatalan Booking -->
        <div v-if="isCancelModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                        <span class="text-rose-600">⚠️</span> Batalkan Reservasi
                    </h3>
                    <button @click="closeCancelModal" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                </div>

                <p class="text-xs text-slate-600">
                    Apakah Anda yakin ingin membatalkan booking <strong class="font-mono text-slate-900">{{ selectedBooking?.booking_code }}</strong>
                    untuk tanggal <strong class="text-slate-900">{{ selectedBooking?.booking_date }}</strong>? Slot waktu antrean akan dibebaskan kembali untuk pelanggan lain.
                </p>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-[11px]">
                    Catatan: Pembatalan mandiri hanya diizinkan paling lambat <strong>{{ cancellation_threshold_hours }} jam</strong> sebelum jadwal kedatangan servis.
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Alasan Pembatalan <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        v-model="cancelForm.cancel_reason"
                        rows="3"
                        placeholder="Contoh: Ada keperluan mendadak / ganti hari..."
                        class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500"
                    ></textarea>
                    <div v-if="cancelForm.errors.cancel_reason" class="text-xs text-rose-600 font-semibold mt-1">
                        {{ cancelForm.errors.cancel_reason }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeCancelModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition"
                    >
                        Kembali
                    </button>
                    <button
                        type="button"
                        @click="submitCancellation"
                        :disabled="cancelForm.processing || !cancelForm.cancel_reason"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm disabled:opacity-50"
                    >
                        <span v-if="cancelForm.processing">Membatalkan...</span>
                        <span v-else>Ya, Batalkan Booking</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
