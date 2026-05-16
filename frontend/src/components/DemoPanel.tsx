import { useState } from 'react'
import { motion } from 'framer-motion'
import { Send, Loader2, CheckCircle, XCircle, Brain, Target, MessageSquare } from 'lucide-react'
import axios from 'axios'

export default function DemoPanel() {
  const [candidateId, setCandidateId] = useState('1')
  const [jobId, setJobId] = useState('1')
  const [loading, setLoading] = useState(false)
  const [result, setResult] = useState<any>(null)
  const [error, setError] = useState('')

  const handleMatch = async () => {
    setLoading(true)
    setError('')
    setResult(null)
    
    try {
      const response = await axios.post('/api/ml/ai-match', {
        candidate_id: parseInt(candidateId),
        job_id: parseInt(jobId),
        include_reasoning: true
      })
      
      if (response.data.success) {
        setResult(response.data.data)
      } else {
        setError(response.data.message || 'Matching failed')
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Network error')
    } finally {
      setLoading(false)
    }
  }

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-400'
    if (score >= 60) return 'text-yellow-400'
    return 'text-red-400'
  }

  const getRankLabel = (rank: string) => {
    const labels: Record<string, string> = {
      'high_fit': 'High Fit',
      'medium_fit': 'Medium Fit',
      'low_fit': 'Low Fit'
    }
    return labels[rank] || rank
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
      {/* Input Panel */}
      <motion.div
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        className="p-6 rounded-2xl glass"
      >
        <h3 className="text-lg font-semibold mb-6 flex items-center gap-2">
          <Brain className="w-5 h-5 text-primary" />
          AI Matching Demo
        </h3>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm text-gray-400 mb-2">Candidate ID</label>
            <input
              type="number"
              value={candidateId}
              onChange={(e) => setCandidateId(e.target.value)}
              className="w-full px-4 py-3 rounded-xl bg-dark-light border border-white/10 text-white focus:border-primary focus:outline-none transition"
              placeholder="Enter candidate ID"
            />
          </div>
          
          <div>
            <label className="block text-sm text-gray-400 mb-2">Job ID</label>
            <input
              type="number"
              value={jobId}
              onChange={(e) => setJobId(e.target.value)}
              className="w-full px-4 py-3 rounded-xl bg-dark-light border border-white/10 text-white focus:border-primary focus:outline-none transition"
              placeholder="Enter job ID"
            />
          </div>
          
          <button
            onClick={handleMatch}
            disabled={loading}
            className="w-full py-3 rounded-xl bg-primary hover:bg-primary-dark transition glow text-white font-medium flex items-center justify-center gap-2 disabled:opacity-50"
          >
            {loading ? (
              <>
                <Loader2 className="w-5 h-5 animate-spin" />
                Processing...
              </>
            ) : (
              <>
                <Send className="w-5 h-5" />
                Run AI Match
              </>
            )}
          </button>
        </div>
        
        {error && (
          <div className="mt-4 p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-2 text-red-400">
            <XCircle className="w-5 h-5" />
            {error}
          </div>
        )}
      </motion.div>

      {/* Result Panel */}
      <motion.div
        initial={{ opacity: 0, x: 20 }}
        animate={{ opacity: 1, x: 0 }}
        className="p-6 rounded-2xl glass"
      >
        <h3 className="text-lg font-semibold mb-6 flex items-center gap-2">
          <Target className="w-5 h-5 text-secondary" />
          Match Result
        </h3>
        
        {result ? (
          <div className="space-y-6">
            {/* Score */}
            <div className="text-center">
              <div className={`text-5xl font-bold ${getScoreColor(result.fit_score)}`}>
                {result.fit_score}
              </div>
              <div className="text-sm text-gray-400 mt-1">Fit Score / 100</div>
              <div className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm mt-2 ${
                result.fit_score >= 80 ? 'bg-green-500/10 text-green-400' :
                result.fit_score >= 60 ? 'bg-yellow-500/10 text-yellow-400' :
                'bg-red-500/10 text-red-400'
              }`}>
                <CheckCircle className="w-4 h-4" />
                {getRankLabel(result.rank_label)}
              </div>
            </div>
            
            {/* Skills */}
            <div className="grid grid-cols-2 gap-4">
              <div className="p-4 rounded-xl bg-green-500/5 border border-green-500/10">
                <div className="text-sm text-green-400 mb-2">Matched Skills</div>
                <div className="flex flex-wrap gap-1">
                  {result.matched_skills.map((skill: string, idx: number) => (
                    <span key={idx} className="px-2 py-1 rounded-lg bg-green-500/10 text-green-400 text-xs">
                      {skill}
                    </span>
                  ))}
                  {result.matched_skills.length === 0 && (
                    <span className="text-xs text-gray-500">None</span>
                  )}
                </div>
              </div>
              
              <div className="p-4 rounded-xl bg-red-500/5 border border-red-500/10">
                <div className="text-sm text-red-400 mb-2">Missing Skills</div>
                <div className="flex flex-wrap gap-1">
                  {result.missing_skills.map((skill: string, idx: number) => (
                    <span key={idx} className="px-2 py-1 rounded-lg bg-red-500/10 text-red-400 text-xs">
                      {skill}
                    </span>
                  ))}
                  {result.missing_skills.length === 0 && (
                    <span className="text-xs text-gray-500">None</span>
                  )}
                </div>
              </div>
            </div>
            
            {/* Reasoning */}
            <div className="p-4 rounded-xl bg-dark-light border border-white/5">
              <div className="flex items-center gap-2 mb-3">
                <MessageSquare className="w-4 h-4 text-accent" />
                <span className="text-sm font-medium">AI Reasoning</span>
              </div>
              <ul className="space-y-2">
                {result.reasoning.map((reason: string, idx: number) => (
                  <li key={idx} className="text-sm text-gray-400 flex items-start gap-2">
                    <span className="text-primary mt-1">•</span>
                    {reason}
                  </li>
                ))}
              </ul>
            </div>
            
            {/* Evidence */}
            <div className="p-4 rounded-xl bg-dark-light border border-white/5">
              <div className="text-sm font-medium mb-3">Grounding Evidence</div>
              <div className="space-y-2">
                {result.evidence.map((ev: any, idx: number) => (
                  <div key={idx} className="text-xs text-gray-500">
                    <span className="text-primary">[{ev.source}]</span> {ev.excerpt.substring(0, 80)}...
                  </div>
                ))}
              </div>
            </div>
            
            {/* Agent Trace */}
            <div className="p-4 rounded-xl bg-dark-light border border-white/5">
              <div className="text-sm font-medium mb-3">Agent Execution Trace</div>
              <div className="space-y-1">
                {result.agent_trace.map((trace: string, idx: number) => (
                  <div key={idx} className="flex items-center gap-2 text-xs">
                    <span className="text-primary">{idx + 1}.</span>
                    <span className="text-gray-400">{trace}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center h-full text-gray-500">
            <Brain className="w-16 h-16 mb-4 opacity-20" />
            <p>Enter IDs and click Run AI Match</p>
          </div>
        )}
      </motion.div>
    </div>
  )
}
