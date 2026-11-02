import type { NextConfig } from "next";

// cacheComponents is left off: it requires Suspense around uncached reads, which starts
// streaming and turns notFound() into HTTP 200. Bookings are never cached anyway.
const nextConfig: NextConfig = {};

export default nextConfig;
