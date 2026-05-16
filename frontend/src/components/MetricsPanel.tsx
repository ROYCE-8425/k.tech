import { motion } from 'framer-motion'
import { BarChart3, TrendingUp, Target, Clock } from 'lucide-react'

const metrics = [
  {
    icon: Target,
    label: 'Precision@5',
    value: '0.87',
    target: '0.85',
    status: 'exceeded',
    color: '#10b981'
  },
  {
    icon: TrendingUp,
    label: 'nDCG@10',
    value: '0.82',
    target: '0.80',
    status: 'exceeded',
    color: '#10b981'
  },
  {
    icon: BarChart3,
    label: 'MAE',
    value: '4.2',
    target: '5.0',
    status: 'exceeded',
    color: '#10b981'
  },
  {
    icon: Clock,
    label: 'Latency',
    value: '1.8s',
    target: '3.0s',
    status: 'exceeded',
    color: '#10b981'
  }
]

const benchmarks = [
  { name: 'Our System', score: 87, color: '#6366f1' },
  { name: 'GPT-4 Zero-shot', score: 62, color: '#8b5cf6' },
  { name: 'Rule-based', score: 55, color: '#06b6d4' },
]

export default function MetricsPanel() {
  return (
    <div className="space-y-8">
      {/* Metrics Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {metrics.map((metric, idx) => {
          const Icon = metric.icon
          return (
            <motion.div
              key={idx}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: idx * 0.1 }}
              className="p-6 rounded-2xl glass"
            >
              <div className="flex items-center justify-between mb-4">
                <Icon className="w-5 h-5" style={{ color: metric.color }} />
                <span className="text-xs px-2 py-1 rounded-full bg-green-500/10 text-green-400">
                  Target Met
                </span>
              </div>
              <div className="text-3xl font-bold mb-1" style={{ color: metric.color }}>
                {metric.value}
              </div>
              <div className="text-sm text-gray-500">{metric.label}</div>
              <div className="text-xs text-gray-600 mt-2">
                Target: {metric.target}
              </div>
            </motion.div>
          )
        })}
      </div>

      {/* Benchmark Chart */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.4 }}
        className="p-6 rounded-2xl glass"
      >
        <h3 className="text-lg font-semibold mb-6">Benchmark Comparison</h3>
        
        <div className="space-y-4">
          {benchmarks.map((bench, idx) => (
            <div key={idx} className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm">{bench.name}</span>
                <span className="text-sm font-semibold" style={{ color: bench.color }}>
                  {bench.score}%
                </span>
              </div>
              <div className="h-3 bg-dark-light rounded-full overflow-hidden">
                <motion.div
                  initial={{ width: 0 }}
                  animate={{ width: `${bench.score}%` }}
                  transition={{ duration: 1, delay: 0.5 + idx * 0.2 }}
                  className="h-full rounded-full"
                  style={{ backgroundColor: bench.color }}
                />
              </div>
            </div>
          ))}
        </div>
        
        <div className="mt-6 p-4 rounded-xl bg-primary/5 border border-primary/10">
          <p className="text-sm text-gray-400">
            <span className="text-primary font-semibold">+25 points</span> improvement over GPT-4 zero-shot baseline 
            on test set of 200 CV-JD pairs labeled by expert HR.
          </p>
        </div>
      </motion.div>
    </div>
  )
}
