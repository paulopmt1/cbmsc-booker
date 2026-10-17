const K = '#2A1F1A'
const R = '#B83A32'
const Y = '#E9B949'
const D = '#8A2A24'
const S = '#F2C6A0'
const G = '#4A5058'

type Run = [x: number, w: number, fill: string]

const boots: Run[] = [[2, 1, K], [3, 2, G], [5, 1, Y], [6, 4, G], [10, 1, Y], [11, 2, G], [13, 1, K]]
const eyes: Run[] = [[4, 1, K], [5, 1, S], [6, 1, K], [7, 2, S], [9, 1, K], [10, 1, S], [11, 1, K]]
const badge: Run[] = [[4, 1, K], [5, 2, R], [7, 2, Y], [9, 2, R], [11, 1, K]]

const ROWS: Run[][] = [
  [[6, 4, K]],
  [[5, 1, K], [6, 4, R], [10, 1, K]],
  [[4, 1, K], [5, 2, R], [7, 2, Y], [9, 2, R], [11, 1, K]],
  badge,
  [[3, 1, K], [4, 8, R], [12, 1, K]],
  [[2, 1, K], [3, 10, D], [13, 1, K]],
  [[4, 1, K], [5, 6, S], [11, 1, K]],
  eyes,
  [[4, 1, K], [5, 6, S], [11, 1, K]],
  [[4, 1, K], [5, 2, S], [7, 2, K], [9, 2, S], [11, 1, K]],
  [[5, 1, K], [6, 4, S], [10, 1, K]],
  [[3, 1, K], [4, 3, G], [7, 2, S], [9, 3, G], [12, 1, K]],
  boots,
  boots,
  boots,
]

/** Bombeiro em pixel-art (16×16), usado no login e na sidebar. */
export function Logo() {
  return (
    <div className="logo">
      <svg viewBox="0 0 16 16" width="64" height="64" shapeRendering="crispEdges" role="img" aria-label="Bombeiro">
        {ROWS.flatMap((runs, i) => runs.map(([x, w, fill]) => <rect key={`${i}-${x}`} x={x} y={i + 1} width={w} height="1" fill={fill} />))}
      </svg>
    </div>
  )
}
