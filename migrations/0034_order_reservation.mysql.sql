-- Migration 0034: reserva das cotas extras (quantidade e números que a rifa reservou), mostrada no card do upsell.
ALTER TABLE orders ADD COLUMN reservation TEXT NULL;
