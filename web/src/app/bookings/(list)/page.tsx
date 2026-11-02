import Link from "next/link";
import { LocalTime } from "@/components/LocalTime";
import { type BookingStatus, listBookings, STATUSES } from "@/lib/bookings";

function href(status: string | undefined, page: number) {
  const query = new URLSearchParams();
  if (status) query.set("status", status);
  if (page > 1) query.set("page", String(page));
  const suffix = query.toString();
  return suffix ? `/bookings?${suffix}` : "/bookings";
}

function parsePage(value: string | string[] | undefined) {
  const page = typeof value === "string" && /^[1-9]\d{0,5}$/.test(value) ? Number(value) : 1;
  return page;
}

export default async function BookingsPage({ searchParams }: PageProps<"/bookings">) {
  const { status, page: rawPage } = await searchParams;
  const page = parsePage(rawPage);

  const validStatus =
    status === undefined || STATUSES.includes(status as BookingStatus);

  const filters = (
    <nav aria-label="Filter by status" className="row">
      <Link href="/bookings" aria-current={status === undefined ? "page" : undefined}>
        All
      </Link>
      {STATUSES.map((option) => (
        <Link
          key={option}
          href={href(option, 1)}
          aria-current={status === option ? "page" : undefined}
        >
          {option}
        </Link>
      ))}
    </nav>
  );

  const heading = (
    <div className="row">
      <h1>Bookings</h1>
      <Link href="/bookings/new">New booking</Link>
    </div>
  );

  // An unknown filter must not fall back to the full list and look like a real result.
  if (!validStatus) {
    return (
      <>
        {heading}
        {filters}
        <p role="alert" className="error">
          Unknown status filter. Choose one of the filters above.
        </p>
      </>
    );
  }

  const activeStatus = status as BookingStatus | undefined;
  const { data, current_page, last_page, total } = await listBookings({
    status: activeStatus,
    page,
  });

  return (
    <>
      {heading}
      {filters}
      {data.length === 0 ? (
        total > 0 ? (
          <p>
            This page is past the end of the results.{" "}
            <Link href={href(activeStatus, last_page)}>Go to the last page</Link>
          </p>
        ) : (
          <p>No bookings found.</p>
        )
      ) : (
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th scope="col">Customer</th>
                <th scope="col">Rego</th>
                <th scope="col">Bay</th>
                <th scope="col">Starts</th>
                <th scope="col">Ends</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
              {data.map((booking) => (
                <tr key={booking.id}>
                  <td>
                    <Link href={`/bookings/${booking.id}`}>{booking.customer_name}</Link>
                  </td>
                  <td>{booking.rego}</td>
                  <td>{booking.service_bay}</td>
                  <td>
                    <LocalTime iso={booking.starts_at} />
                  </td>
                  <td>
                    <LocalTime iso={booking.ends_at} />
                  </td>
                  <td>{booking.status}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {data.length > 0 && (
        <nav aria-label="Pagination" className="row">
          {current_page > 1 && (
            <Link href={href(activeStatus, current_page - 1)} rel="prev">
              Previous
            </Link>
          )}
          <span>
            Page {current_page} of {last_page} ({total} total)
          </span>
          {current_page < last_page && (
            <Link href={href(activeStatus, current_page + 1)} rel="next">
              Next
            </Link>
          )}
        </nav>
      )}
    </>
  );
}
