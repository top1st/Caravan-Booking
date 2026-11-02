import { useSyncExternalStore } from "react";

const subscribe = () => () => {};

// The server cannot know the viewer's zone, so it renders UTC (labelled as such)
// and the browser switches to local time straight after hydration without a mismatch.
export function useTimeZone() {
  return useSyncExternalStore(
    subscribe,
    () => Intl.DateTimeFormat().resolvedOptions().timeZone,
    () => "UTC",
  );
}

// sv-SE formats as "2026-12-01 09:00", which is one character from a datetime-local value.
export function isoToLocalInput(iso: string, timeZone: string) {
  return new Intl.DateTimeFormat("sv-SE", {
    timeZone,
    dateStyle: "short",
    timeStyle: "short",
  })
    .format(new Date(iso))
    .replace(" ", "T");
}

// Must run in the browser: a datetime-local value has no zone, so it is read as browser time.
export function localInputToIso(local: string) {
  return local ? new Date(local).toISOString() : "";
}
