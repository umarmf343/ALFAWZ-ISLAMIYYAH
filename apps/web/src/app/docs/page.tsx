import Link from 'next/link';

function getApiBase(): string {
  const base = process.env.NEXT_PUBLIC_API_BASE ?? 'http://localhost:8000/api';
  return base.replace(/\/?api\/?$/i, '');
}

export default function DocumentationPortal() {
  const apiBase = getApiBase();
  const redocUrl = `${apiBase}/docs/api`;
  const specUrl = `${apiBase}/docs/openapi.yaml`;

  return (
    <div className="min-h-screen bg-slate-50 py-12">
      <div className="mx-auto max-w-5xl px-6">
        <section className="mb-12 text-center">
          <h1 className="text-4xl font-bold text-slate-900 sm:text-5xl">
            Documentation Portal
          </h1>
          <p className="mt-4 text-lg text-slate-600">
            Explore integration guides, API references, and SDK downloads for the AlFawz Qur&apos;an Institute platform.
          </p>
        </section>

        <div className="grid gap-6 md:grid-cols-2">
          <article className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 className="text-2xl font-semibold text-slate-900">Interactive API Reference</h2>
            <p className="mt-3 text-slate-600">
              Browse every endpoint exposed by the platform, view authentication requirements, and try out requests using Redoc.
            </p>
            <Link
              href={redocUrl}
              className="mt-6 inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
              View API Docs
            </Link>
          </article>

          <article className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 className="text-2xl font-semibold text-slate-900">OpenAPI Specification</h2>
            <p className="mt-3 text-slate-600">
              Download the OpenAPI 3 contract to integrate with your tooling or generate clients in your preferred language.
            </p>
            <Link
              href={specUrl}
              className="mt-6 inline-flex items-center justify-center rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
            >
              Download Specification
            </Link>
          </article>
        </div>

        <section className="mt-12 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold text-slate-900">TypeScript Client</h2>
          <p className="mt-3 text-slate-600">
            Generated types live in <code className="rounded bg-slate-100 px-1">apps/web/src/lib/generated</code>. Run
            <code className="ml-1 rounded bg-slate-100 px-1">php artisan openapi:sync</code> to regenerate after updating the contract.
          </p>
        </section>
      </div>
    </div>
  );
}
