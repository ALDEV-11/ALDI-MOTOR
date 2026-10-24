export type UserRole = 'customer' | 'admin';

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string;
    role: UserRole;
    role_label: string;
    whatsapp_opt_in: boolean;
    no_show_count: number;
    blocked_until: string | null;
    is_blocked: boolean;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
    };
    errors: Record<string, string>;
};

export interface Vehicle {
    id: number;
    user_id?: number;
    plate_number: string;
    brand: string;
    model: string;
    year: number;
    bookings_count?: number;
    created_at?: string;
}

export interface ServiceItem {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    price_estimate: number;
    is_active?: boolean;
}

export interface BookingSlot {
    time: string;
    label: string;
    capacity: number;
    booked: number;
    remaining: number;
    status: 'tersedia' | 'hampir penuh' | 'penuh';
}

export interface BookingItem {
    id: number;
    booking_code: string;
    booking_date: string;
    booking_date_formatted?: string;
    queue_order: number;
    estimated_arrival_at: string;
    status: 'pending' | 'confirmed' | 'checked_in' | 'in_progress' | 'completed' | 'cancelled';
    status_label: string;
    status_color: string;
    complaint?: string | null;
    cancel_reason?: string | null;
    vehicle: Vehicle | null;
    service: ServiceItem | null;
    checked_in_at?: string | null;
    started_at?: string | null;
    finished_at?: string | null;
    cancelled_at?: string | null;
}

