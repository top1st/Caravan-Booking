import Link from "next/link";
import { BookingForm } from "@/components/BookingForm";
import { updateBookingAction } from "@/lib/actions";
import { loadBooking } from "../load-booking";

export default async function EditBookingPage({ params }: PageProps<"/bookings/[id]/edit">) {
  const { id } = await params;
  const booking = await loadBooking(id);

  return (
    <>
      <h1>Edit booking #{booking.id}</h1>
      <BookingForm
        action={updateBookingAction.bind(null, id)}
        initialValues={{
          customer_name: booking.customer_name,
          customer_email: booking.customer_email,
          rego: booking.rego,
          service_bay: booking.service_bay,
          starts_at: booking.starts_at,
          ends_at: booking.ends_at,
          status: booking.status,
          notes: booking.notes ?? "",
        }}
        submitLabel="Save changes"
      />
      <p>
        <Link href={`/bookings/${booking.id}`}>Back to booking</Link>
      </p>
    </>
  );
}
