"use client";

import { useEffect } from "react";

export default function BookingsError({
  error,
  retry,
}: {
  error: unknown;
  retry: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <div role="alert">
      <h1>Something went wrong</h1>
      <p>The bookings service could not complete that request. Is the API running?</p>
      <button onClick={() => retry()}>Try again</button>
    </div>
  );
}
