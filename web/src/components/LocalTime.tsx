"use client";

import { useTimeZone } from "@/lib/time";

export function LocalTime({ iso }: { iso: string }) {
  const timeZone = useTimeZone();
  return (
    <time dateTime={iso}>
      {new Intl.DateTimeFormat("en-AU", {
        timeZone,
        dateStyle: "medium",
        timeStyle: "short",
      }).format(new Date(iso))}{" "}
      ({timeZone})
    </time>
  );
}
