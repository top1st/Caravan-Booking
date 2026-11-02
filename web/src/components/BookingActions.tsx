"use client";

import { useFormStatus } from "react-dom";
import { cancelBookingAction, deleteBookingAction } from "@/lib/actions";

function SubmitButton({ label }: { label: string }) {
  const { pending } = useFormStatus();
  return (
    <button type="submit" disabled={pending}>
      {pending ? "Working…" : label}
    </button>
  );
}

export function BookingActions({ id, cancelled }: { id: string; cancelled: boolean }) {
  return (
    <div className="row">
      {!cancelled && (
        <form action={cancelBookingAction.bind(null, id)}>
          <SubmitButton label="Cancel booking" />
        </form>
      )}
      <form
        action={deleteBookingAction.bind(null, id)}
        onSubmit={(event) => {
          if (!window.confirm("Permanently delete this booking?")) {
            event.preventDefault();
          }
        }}
      >
        <SubmitButton label="Delete booking" />
      </form>
    </div>
  );
}
