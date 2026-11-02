export const STATUSES = ["pending", "confirmed", "cancelled"] as const;
export type BookingStatus = (typeof STATUSES)[number];

export type Booking = {
  id: number;
  customer_name: string;
  customer_email: string;
  rego: string;
  service_bay: string;
  starts_at: string;
  ends_at: string;
  status: BookingStatus;
  notes: string | null;
  created_at: string;
  updated_at: string;
};

export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type BookingInput = Partial<
  Pick<
    Booking,
    | "customer_name"
    | "customer_email"
    | "rego"
    | "service_bay"
    | "starts_at"
    | "ends_at"
    | "status"
    | "notes"
  >
>;

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const baseUrl = process.env.LARAVEL_API_URL;
  if (!baseUrl) throw new Error("LARAVEL_API_URL is not set");

  const response = await fetch(`${baseUrl}${path}`, {
    ...init,
    cache: "no-store",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
  });

  if (response.status === 204) return undefined as T;

  // A proxy or crash page may not be JSON; the status still decides what happens next.
  const body = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new ApiError(
      response.status,
      body.message ?? `API responded with ${response.status}`,
      body.errors,
    );
  }
  return body;
}

export function listBookings(filters: { status?: BookingStatus; page: number }) {
  const query = new URLSearchParams({ page: String(filters.page) });
  if (filters.status) query.set("status", filters.status);
  return request<Paginated<Booking>>(`/bookings?${query}`);
}

export function getBooking(id: string) {
  return request<Booking>(`/bookings/${encodeURIComponent(id)}`);
}

export function createBooking(input: BookingInput) {
  return request<Booking>("/bookings", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function replaceBooking(id: string, input: BookingInput) {
  return request<Booking>(`/bookings/${encodeURIComponent(id)}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export function patchBooking(id: string, input: BookingInput) {
  return request<Booking>(`/bookings/${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: JSON.stringify(input),
  });
}

export function deleteBooking(id: string) {
  return request<void>(`/bookings/${encodeURIComponent(id)}`, {
    method: "DELETE",
  });
}
