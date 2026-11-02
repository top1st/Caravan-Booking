import type { Metadata } from "next";
import Link from "next/link";
import "./globals.css";

export const metadata: Metadata = {
  title: "Workshop Bookings",
  description: "Service bay bookings for a caravan dealer",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en">
      <body>
        <header>
          <Link href="/bookings">Workshop Bookings</Link>
        </header>
        <main>{children}</main>
      </body>
    </html>
  );
}
