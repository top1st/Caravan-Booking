"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import {
  ApiError,
  type Booking,
  type BookingInput,
  createBooking,
  deleteBooking,
  patchBooking,
  replaceBooking,
} from "@/lib/bookings";

export type FormState = {
  message?: string;
  errors: Record<string, string[]>;
  values: Record<string, string>;
};

const FIELDS = [
  "customer_name",
  "customer_email",
  "rego",
  "service_bay",
  "starts_at",
  "ends_at",
  "status",
  "notes",
];

function readValues(formData: FormData) {
  return Object.fromEntries(
    FIELDS.map((field) => [field, String(formData.get(field) ?? "")]),
  );
}

// Laravel does the real validation; this only turns its answer into form state.
function failure(error: unknown, values: Record<string, string>): FormState {
  if (error instanceof ApiError && error.status === 422) {
    return {
      message: "Please fix the highlighted fields.",
      errors: error.errors,
      values,
    };
  }
  if (error instanceof ApiError && error.status === 404) {
    return { message: "This booking no longer exists.", errors: {}, values };
  }
  console.error(error);
  return {
    message: "The booking could not be saved. Nothing was changed; please try again.",
    errors: {},
    values,
  };
}

function revalidateBooking(id: string | number) {
  revalidatePath("/bookings");
  revalidatePath(`/bookings/${id}`);
}

export async function createBookingAction(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const values = readValues(formData);
  let booking: Booking;
  try {
    booking = await createBooking(values as BookingInput);
  } catch (error) {
    return failure(error, values);
  }
  revalidateBooking(booking.id);
  redirect(`/bookings/${booking.id}`); // Outside the try: redirect works by throwing.
}

export async function updateBookingAction(
  id: string,
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const values = readValues(formData);
  try {
    await replaceBooking(id, values as BookingInput);
  } catch (error) {
    return failure(error, values);
  }
  revalidateBooking(id);
  redirect(`/bookings/${id}`); // Outside the try: redirect works by throwing.
}

export async function cancelBookingAction(id: string) {
  await patchBooking(id, { status: "cancelled" });
  revalidateBooking(id);
}

export async function deleteBookingAction(id: string) {
  try {
    await deleteBooking(id);
  } catch (error) {
    // Already gone (double submit, another tab) is the outcome the user asked for.
    if (!(error instanceof ApiError && error.status === 404)) throw error;
  }
  revalidateBooking(id);
  redirect("/bookings");
}
