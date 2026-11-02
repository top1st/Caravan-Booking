import Link from "next/link";
import { BookingActions } from "@/components/BookingActions";
import { LocalTime } from "@/components/LocalTime";
import { loadBooking } from "./load-booking";

// No loading.tsx here or above: a Suspense boundary would start streaming and make a missing booking HTTP 200.
export default async function BookingPage({ params }: PageProps<"/bookings/[id]">) {
  const { id } = await params;
  const booking = await loadBooking(id);

  return (
    <>
      <h1>Booking #{booking.id}</h1>
      <dl>
        <dt>Customer</dt>
        <dd>{booking.customer_name}</dd>
        <dt>Email</dt>
        <dd>{booking.customer_email}</dd>
        <dt>Rego</dt>
        <dd>{booking.rego}</dd>
        <dt>Service bay</dt>
        <dd>{booking.service_bay}</dd>
        <dt>Starts</dt>
        <dd>
          <LocalTime iso={booking.starts_at} />
        </dd>
        <dt>Ends</dt>
        <dd>
          <LocalTime iso={booking.ends_at} />
        </dd>
        <dt>Status</dt>
        <dd>{booking.status}</dd>
        <dt>Notes</dt>
        <dd>{booking.notes ?? "None"}</dd>
      </dl>
      <div className="row">
        <Link href={`/bookings/${booking.id}/edit`}>Edit booking</Link>
        <BookingActions id={id} cancelled={booking.status === "cancelled"} />
      </div>
      <p>
        <Link href="/bookings">Back to bookings</Link>
      </p>
    </>
  );
}
