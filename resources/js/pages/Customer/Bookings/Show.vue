<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';

interface StatusTimelineItem {
    id: number;
    from_status: string | null;
    to_status: string;
    to_status_label: string;
    note: string | null;
    actor_name: string;
    created_at: string;
}

interface NotificationItem {
    id: number;
    type: string;
    phone: string;
    message: string;
    sent_at: string;
}

interface Props {
    booking: {
        id: number;
        booking_code: string;
        booking_date: string;
        booking_date_formatted: string;
        queue_order: number;
        estimated_arrival_at: string;
        status: string;
        status_label: string;
        status_color: string;
        complaint: string | null;
        cancel_reason: string | null;
        checked_in_at: string | null;
        started_at: string | null;
        finished_at: string | null;
        cancelled_at: string | null;
        vehicle: {
            id: number;
            plate_number: string;
            brand: string;
            model: string;
            year: number;
        } | null;
        service: {
            id: number;
            name: string;
            description: string | null;
            duration_minutes: number;
            price_estimate: number;
        } | null;
        timeline: StatusTimelineItem[];
        notifications: NotificationItem[];
    };
    can_cancel: boolean;
    cancellation_threshold_hours: number;
    workshop_info: {
        name: string;
        phone: string;
        address: string;
    };
}

const props = defineProps<Props>();

// Modal pembatalan booking
const isCancelModalOpen = ref(false);
const cancelForm = useForm({
    cancel_reason: '',
});

const submitCancellation = () => {
    cancelForm.post(`/beranda/bookings/${props.booking.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            isCancelModalOpen.value = false;
        },
    });
};

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
    <AppLayout :title="`Detail Booking ${booking.booking_code}`">
        <Head :title="`Detail Booking ${booking.booking_code} - ALDI-MOTOR`" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header Navigasi Balik -->
            <div class="flex items-center justify-between">
                <Link
                    href="/beranda/bookings"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition"
                >
                    ← Kembali ke Booking Saya
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        v-if="can_cancel"
                        type="button"
                        @click="isCancelModalOpen = true"
                        class="px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold transition"
                    >
                        ✕ Batalkan Booking Ini
                    </button>
                </div>
            </div>

            <!-- Card Utama Detail Booking -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <!-- Banner Status Card -->
                <div class="p-6 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">
                            Tiket Antrean Servis Resmi
                        </div>
                        <h1 class="text-2xl font-black font-mono tracking-tight mt-0.5">
                            {{ booking.booking_code }}
                        </h1>
                        <p class="text-xs text-slate-300 mt-1">
                            {{ booking.booking_date_formatted }} • Estimasi kedatangan: <strong class="text-white">{{ booking.estimated_arrival_at }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur border border-white/10 text-center">
                            <div class="text-[10px] text-slate-300 font-bold uppercase">Nomor Antrean</div>
                            <div class="text-2xl font-black font-mono text-red-400">#{{ booking.queue_order }}</div>
                        </div>

                        <span class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider border shadow-xs"
                              :class="booking.status_color">
                            {{ booking.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Informasi Rinci Grid -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100">
                    <!-- Info Kendaraan -->
                    <div class="space-y-3">
                        <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                            🏍️ Kendaraan
                        </h3>
                        <div v-if="booking.vehicle" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs">
                            <div class="font-black text-slate-900 text-base font-mono">
                                {{ booking.vehicle.plate_number }}
                            </div>
                            <div class="font-bold text-slate-700">
                                {{ booking.vehicle.brand }} {{ booking.vehicle.model }} ({{ booking.vehicle.year }})
                            </div>
                        </div>
                    </div>

                    <!-- Info Layanan Servis -->
                    <div class="space-y-3">
                        <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                            🔧 Layanan Servis
                        </h3>
                        <div v-if="booking.service" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="font-black text-slate-900 text-base">
                                    {{ booking.service.name }}
                                </div>
                                <span class="font-mono font-bold text-slate-800 text-sm">
                                    {{ formatRupiah(booking.service.price_estimate) }}
                                </span>
                            </div>
                            <div class="text-slate-500">
                                Estimasi durasi pengerjaan: {{ booking.service.duration_minutes }} menit
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Catatan Keluhan & Alasan Batal jika ada -->
                <div class="p-6 border-b border-slate-100 text-xs space-y-3">
                    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                        📝 Catatan Keluhan dari Pelanggan
                    </h3>
                    <p class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 text-slate-700 italic">
                        "{{ booking.complaint || 'Tidak ada catatan keluhan khusus.' }}"
                    </p>

                    <div v-if="booking.status === 'cancelled' && booking.cancel_reason" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800">
                        <strong>Alasan Pembatalan:</strong> {{ booking.cancel_reason }}
                    </div>
                </div>

                <!-- Timeline Perjalanan Status Booking -->
                <div class="p-6 space-y-4">
                    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                        ⏱️ Riwayat Jejak Status Antrean (Audit Log)
                    </h3>

                    <div class="relative border-l-2 border-slate-200 ml-3 space-y-6">
                        <div
                            v-for="log in booking.timeline"
                            :key="log.id"
                            class="relative pl-6"
                        >
                            <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-red-600 border-2 border-white ring-2 ring-red-200"></span>
                            <div class="text-xs font-extrabold text-slate-900">
                                {{ log.to_status_label }}
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ log.created_at }} • Oleh: <strong class="text-slate-600">{{ log.actor_name }}</strong>
                            </div>
                            <div v-if="log.note" class="text-xs text-slate-600 mt-1 italic">
                                "{{ log.note }}"
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Notifikasi WhatsApp jika ada -->
                <div v-if="booking.notifications.length > 0" class="p-6 bg-slate-50/60 border-t border-slate-100 space-y-3">
                    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                        💬 Notifikasi WhatsApp Terkirim
                    </h3>

                    <div class="space-y-2">
                        <div
                            v-for="n in booking.notifications"
                            :key="n.id"
                            class="p-3 rounded-xl bg-white border border-slate-200 text-xs space-y-1"
                        >
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span class="font-mono font-bold text-slate-600">{{ n.phone }}</span>
                                <span>{{ n.sent_at }}</span>
                            </div>
                            <p class="text-slate-700">{{ n.message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Batal -->
        <div v-if="isCancelModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-base text-slate-900">Batalkan Booking</h3>
                    <button @click="isCancelModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                </div>

                <p class="text-xs text-slate-600">
                    Konfirmasi pembatalan tiket reservasi <strong class="font-mono text-slate-900">{{ booking.booking_code }}</strong>.
                </p>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Alasan Pembatalan <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        v-model="cancelForm.cancel_reason"
                        rows="3"
                        placeholder="Tulis alasan mengapa Anda membatalkan..."
                        class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500"
                    ></textarea>
                    <div v-if="cancelForm.errors.cancel_reason" class="text-xs text-rose-600 font-semibold mt-1">
                        {{ cancelForm.errors.cancel_reason }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="isCancelModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        Tutup
                    </button>
                    <button
                        type="button"
                        @click="submitCancellation"
                        :disabled="cancelForm.processing || !cancelForm.cancel_reason"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50"
                    >
                        Konfirmasi Batal
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
