const DEFAULT_DEVICE_COUNT = 5000;
const CATEGORIES = [
  'car', 'pickup', 'van', 'truck', 'motorcycle', 'bus', 'tractor', 'boat', 'default',
];
const STATUSES = ['online', 'offline', 'unknown'];

function pseudoRandom(index) {
  const x = Math.sin(index * 9301 + 49297) * 233280;
  return x - Math.floor(x);
}

export function createSyntheticDevices(count = DEFAULT_DEVICE_COUNT) {
  return Array.from({ length: count }, (_, index) => {
    const id = index + 1;
    const category = CATEGORIES[index % CATEGORIES.length];
    const status = STATUSES[index % STATUSES.length];

    return {
      id,
      name: 'Dispositivo sintético ' + String(id).padStart(4, '0'),
      uniqueId: 'synthetic-' + String(id).padStart(4, '0'),
      category,
      status,
      groupId: 0,
      disabled: false,
      lastUpdate: '2026-06-09T00:00:00.000Z',
      attributes: {
        placa: 'SYN' + String(id).padStart(4, '0'),
        'tarkan.color': ((id * 17) % 360) + '|1|1.8',
        'tarkan.color_extra': ((id * 29) % 360) + '|1|1.8',
      },
    };
  });
}

export function createSyntheticPositions(devices) {
  return devices.map((device, index) => {
    const latJitter = pseudoRandom(index + 1) * 1.8 - 0.9;
    const lngJitter = pseudoRandom(index + 5001) * 1.8 - 0.9;

    return {
      id: device.id,
      deviceId: device.id,
      latitude: -29.942484 + latJitter,
      longitude: -50.990526 + lngJitter,
      course: (index * 37) % 360,
      speed: index % 7 === 0 ? 0 : 28 + (index % 80),
      accuracy: 12 + (index % 25),
      fixTime: '2026-06-09T00:00:00.000Z',
      attributes: {
        motion: index % 3 !== 0,
        ignition: index % 4 !== 0,
      },
    };
  });
}

export function summarizeMarkerScale({
  deviceCount,
  markerRegistrySize,
  pendingMarkersAfterFlush,
  filterBefore,
  filterAfter,
  elapsedMs,
  clusterOptions = {},
} = {}) {
  return {
    deviceCount,
    markerRegistrySize,
    pendingMarkersAfterFlush,
    filterBefore,
    filterAfter,
    elapsedMs,
    duplicateGrowth: Math.max(0, (markerRegistrySize || 0) - (deviceCount || 0)),
    clusterOptions: {
      chunkedLoading: clusterOptions.chunkedLoading === true,
      removeOutsideVisibleBounds: clusterOptions.removeOutsideVisibleBounds === true,
      animate: clusterOptions.animate === true,
    },
  };
}

export function canRunMapScaleProbe() {
  return process.env.NODE_ENV !== 'production' || window.location.search.indexOf('mapScaleProbe=1') !== -1;
}

export function createMapScaleProbeFixture(count = DEFAULT_DEVICE_COUNT) {
  if (!canRunMapScaleProbe()) {
    throw new Error('mapScaleProbe is dev/test-only and must be enabled intentionally');
  }

  const devices = createSyntheticDevices(count);
  return {
    devices,
    positions: createSyntheticPositions(devices),
  };
}
