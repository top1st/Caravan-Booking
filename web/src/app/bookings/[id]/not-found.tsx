import Link from "next/link";

export default function BookingNotFound() {
  return (
    <>
      <h1>Booking not found</h1>
      <p>This booking does not exist or has been deleted.</p>
      <Link href="/bookings">Back to bookings</Link>
    </>
  );
}
