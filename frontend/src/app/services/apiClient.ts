import type { ApiEnvelope } from '../types/api';
import { ERROR_MESSAGES } from '../constants/messages';

const BASE_URL = import.meta.env.VITE_API_BASE_URL as string | undefined;

if (!BASE_URL) {
  console.error(
    '[apiClient] VITE_API_BASE_URL is not defined. '
    + 'Add it to your .env.development file (see .env.example).'
  );
}

const getBaseUrl = (): string => {
  if (!BASE_URL) throw new Error(ERROR_MESSAGES.missingConfig);
  return BASE_URL;
};

export const post = async <T>(path: string, body?: unknown, signal?: AbortSignal): Promise<T> => {
  const res = await fetch(`${getBaseUrl()}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: body !== undefined ? JSON.stringify(body) : undefined,
    signal,
  });

  const json = (await res.json()) as ApiEnvelope<T> | { message?: string };

  if (!res.ok) {
    const msg = (json as { message?: string }).message ?? `Request failed: ${res.status}`;
    throw new Error(msg);
  }

  const envelope = json as ApiEnvelope<T>;
  if (!envelope.success) {
    throw new Error(ERROR_MESSAGES.apiError);
  }

  return envelope.data;
};

export const get = async <T>(path: string, signal?: AbortSignal): Promise<T> => {
  const res = await fetch(`${getBaseUrl()}${path}`, {
    method: 'GET',
    headers: { Accept: 'application/json' },
    signal,
  });

  const json = (await res.json()) as ApiEnvelope<T> | { message?: string };

  if (!res.ok) {
    const msg = (json as { message?: string }).message ?? `Request failed: ${res.status}`;
    throw new Error(msg);
  }

  const envelope = json as ApiEnvelope<T>;
  if (!envelope.success) {
    throw new Error(ERROR_MESSAGES.apiError);
  }

  return envelope.data;
};
