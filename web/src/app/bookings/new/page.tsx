import Link from "next/link";
import { BookingForm } from "@/components/BookingForm";
import { createBookingAction } from "@/lib/actions";

export default function NewBookingPage() {
  return (
    <>
      <h1>New booking</h1>
      <BookingForm
        action={createBookingAction}
        initialValues={{ status: "pending", starts_at: "", ends_at: "" }}
        submitLabel="Create booking"
      />
      <p>
        <Link href="/bookings">Back to bookings</Link>
      </p>
    </>
  );
}
