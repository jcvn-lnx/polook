const CLUSTER_TIERS = {
  sm: { min: 2, max: 9, size: 36 },
  md: { min: 10, max: 99, size: 44 },
  lg: { min: 100, max: 999, size: 52 },
  xl: { min: 1000, max: Infinity, size: 60 },
};

function getClusterTier(count) {
  if (count >= CLUSTER_TIERS.xl.min) return 'xl';
  if (count >= CLUSTER_TIERS.lg.min) return 'lg';
  if (count >= CLUSTER_TIERS.md.min) return 'md';
  return 'sm';
}

export function createTarkanClusterIcon(cluster, L) {
  const count = Number(cluster.getChildCount()) || 0;
  const tier = getClusterTier(count);
  const size = CLUSTER_TIERS[tier].size;
  const label = count + ' dispositivos agrupados';

  const childMarkers = cluster.getAllChildMarkers();
  let onlineCount = 0;
  childMarkers.forEach((m) => {
    const status = m.options && m.options.device && m.options.device.status;
    if (status === 'online') onlineCount++;
  });
  const ratio = count > 0 ? onlineCount / count : 0;
  let bgColor;
  if (ratio >= 0.75) bgColor = '#22c55e';
  else if (ratio >= 0.35) bgColor = '#eab308';
  else bgColor = '#ef4444';

  return L.divIcon({
    html: '<div class="tarkan-cluster tarkan-cluster--' + tier + '" style="background:' + bgColor + '" aria-label="' + label + '" title="' + label + '">' + count + '</div>',
    className: 'tarkan-cluster-icon tarkan-cluster-icon--' + tier,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
  });
}

export function createMarkerClusterOptions({ L, onProgress } = {}) {
  const options = {
    chunkedLoading: true,
    chunkInterval: 200,
    chunkDelay: 50,
    removeOutsideVisibleBounds: true,
    animate: true,
    spiderfyOnMaxZoom: true,
    spiderfyOnEveryZoom: true,
    zoomToBoundsOnClick: false,
    showCoverageOnHover: false,
    animateAddingMarkers: false,
    maxClusterRadius: (zoom) => (zoom < 8 ? 96 : zoom < 12 ? 72 : 48),
    iconCreateFunction: (cluster) => createTarkanClusterIcon(cluster, L),
  };

  if (onProgress) {
    options.chunkProgress = onProgress;
  }

  return options;
}

export { CLUSTER_TIERS };
