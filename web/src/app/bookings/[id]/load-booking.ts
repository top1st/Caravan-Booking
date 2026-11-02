import { notFound } from "next/navigation";
import { ApiError, getBooking } from "@/lib/bookings";

export async function loadBooking(id: string) {
  try {
    return await getBooking(id);
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) notFound();
    throw error; // Transport and 5xx failures belong to the error boundary, not a 404.
  }
}
