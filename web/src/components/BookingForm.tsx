"use client";

import { useActionState, useState } from "react";
import type { FormState } from "@/lib/actions";
import { STATUSES } from "@/lib/bookings";
import { isoToLocalInput, localInputToIso, useTimeZone } from "@/lib/time";

type Props = {
  action: (state: FormState, formData: FormData) => Promise<FormState>;
  initialValues: Record<string, string>;
  submitLabel: string;
};

function FieldError({ name, errors }: { name: string; errors?: string[] }) {
  if (!errors?.length) return null;
  return (
    <p id={`${name}-error`} className="error" role="alert">
      {errors.join(" ")}
    </p>
  );
}

function fieldProps(name: string, errors?: string[]) {
  return {
    id: name,
    "aria-invalid": errors?.length ? true : undefined,
    "aria-describedby": errors?.length ? `${name}-error` : undefined,
  };
}

// The visible input holds browser-local time; the hidden one submits the UTC instant.
function DateTimeField({
  name,
  label,
  iso,
  errors,
}: {
  name: string;
  label: string;
  iso: string;
  errors?: string[];
}) {
  const timeZone = useTimeZone();
  const [edited, setEdited] = useState<string | null>(null);

  return (
    <div className="field">
      <label htmlFor={name}>
        {label} ({timeZone})
      </label>
      <input
        {...fieldProps(name, errors)}
        type="datetime-local"
        required
        value={edited ?? (iso ? isoToLocalInput(iso, timeZone) : "")}
        onChange={(event) => setEdited(event.target.value)}
      />
      <input
        type="hidden"
        name={name}
        value={edited === null ? iso : localInputToIso(edited)}
      />
      <FieldError name={name} errors={errors} />
    </div>
  );
}

export function BookingForm({ action, initialValues, submitLabel }: Props) {
  const [state, formAction, pending] = useActionState(action, {
    errors: {},
    values: initialValues,
  });
  const { errors, values } = state;

  const textField = (name: string, label: string, type = "text") => (
    <div className="field">
      <label htmlFor={name}>{label}</label>
      <input
        {...fieldProps(name, errors[name])}
        name={name}
        type={type}
        required
        defaultValue={values[name]}
      />
      <FieldError name={name} errors={errors[name]} />
    </div>
  );

  return (
    <form action={formAction}>
      {state.message && (
        <p className="error" role="alert">
          {state.message}
        </p>
      )}
      {textField("customer_name", "Customer name")}
      {textField("customer_email", "Customer email", "email")}
      {textField("rego", "Rego")}
      {textField("service_bay", "Service bay")}
      <DateTimeField
        name="starts_at"
        label="Starts at"
        iso={initialValues.starts_at}
        errors={errors.starts_at}
      />
      <DateTimeField
        name="ends_at"
        label="Ends at"
        iso={initialValues.ends_at}
        errors={errors.ends_at}
      />
      <div className="field">
        <label htmlFor="status">Status</label>
        {/* Keyed because React's post-action form reset ignores a changed defaultValue on select. */}
        <select
          key={values.status}
          {...fieldProps("status", errors.status)}
          name="status"
          defaultValue={values.status}
        >
          {STATUSES.map((status) => (
            <option key={status} value={status}>
              {status}
            </option>
          ))}
        </select>
        <FieldError name="status" errors={errors.status} />
      </div>
      <div className="field">
        <label htmlFor="notes">Notes (optional)</label>
        <textarea
          {...fieldProps("notes", errors.notes)}
          name="notes"
          rows={3}
          defaultValue={values.notes}
        />
        <FieldError name="notes" errors={errors.notes} />
      </div>
      <button type="submit" disabled={pending}>
        {pending ? "Saving…" : submitLabel}
      </button>
    </form>
  );
}
