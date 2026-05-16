import { motion, AnimatePresence } from 'framer-motion'
import { ArrowRight } from 'lucide-react'

interface Agent {
  id: number
  name: string
  icon: React.ElementType
  color: string
  desc: string
  detail: string
}

interface AgentFlowProps {
  agents: Agent[]
  activeAgent: number
  setActiveAgent: (id: number) => void
}

export default function AgentFlow({ agents, activeAgent, setActiveAgent }: AgentFlowProps) {
  return (
    <div className="relative py-8">
      {/* Flow Line */}
      <div className="hidden md:block absolute top-1/2 left-0 right-0 h-0.5 bg-gradient-to-r from-primary via-secondary to-accent -translate-y-1/2" />
      
      <div className="flex flex-col md:flex-row items-center justify-between gap-4 relative z-10">
        {agents.map((agent, idx) => {
          const Icon = agent.icon
          const isActive = activeAgent === idx
          
          return (
            <motion.div
              key={agent.id}
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: idx * 0.15 }}
              onClick={() => setActiveAgent(idx)}
              className="flex flex-col items-center gap-2 cursor-pointer group"
            >
              {/* Agent Node */}
              <motion.div
                animate={{
                  scale: isActive ? 1.1 : 1,
                  boxShadow: isActive 
                    ? `0 0 30px ${agent.color}40` 
                    : '0 0 0px transparent'
                }}
                className={`relative w-16 h-16 rounded-2xl flex items-center justify-center transition-all duration-300 ${
                  isActive ? 'bg-white/10' : 'glass'
                }`}
                style={{ borderColor: isActive ? agent.color : undefined }}
              >
                {/* Pulse ring for active */}
                {isActive && (
                  <div 
                    className="absolute inset-0 rounded-2xl pulse-ring"
                    style={{ border: `2px solid ${agent.color}` }}
                  />
                )}
                
                <Icon 
                  className="w-7 h-7 transition-colors" 
                  style={{ color: isActive ? agent.color : '#9ca3af' }}
                />
                
                {/* Step number */}
                <div 
                  className="absolute -top-2 -right-2 w-5 h-5 rounded-full text-xs flex items-center justify-center font-bold"
                  style={{ 
                    backgroundColor: agent.color,
                    color: 'white'
                  }}
                >
                  {idx + 1}
                </div>
              </motion.div>
              
              {/* Agent Name */}
              <div className="text-center">
                <p className={`text-xs font-medium transition-colors ${
                  isActive ? 'text-white' : 'text-gray-500'
                }`}>
                  {agent.name}
                </p>
              </div>
              
              {/* Arrow (except last) */}
              {idx < agents.length - 1 && (
                <div className="hidden md:block absolute -right-8 top-1/2 -translate-y-1/2">
                  <ArrowRight className="w-4 h-4 text-gray-600" />
                </div>
              )}
            </motion.div>
          )
        })}
      </div>
      
      {/* Active Agent Detail */}
      <AnimatePresence mode="wait">
        <motion.div
          key={activeAgent}
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: -10 }}
          className="mt-8 p-6 rounded-2xl glass"
        >
          <div className="flex items-start gap-4">
            <div 
              className="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
              style={{ backgroundColor: `${agents[activeAgent].color}20` }}
            >
              {(() => {
                const Icon = agents[activeAgent].icon
                return <Icon className="w-6 h-6" style={{ color: agents[activeAgent].color }} />
              })()}
            </div>
            <div>
              <h3 className="font-semibold text-lg mb-1">{agents[activeAgent].name}</h3>
              <p className="text-gray-400 text-sm">{agents[activeAgent].detail}</p>
            </div>
          </div>
        </motion.div>
      </AnimatePresence>
    </div>
  )
}
