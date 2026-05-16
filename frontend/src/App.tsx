import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { 
  Brain, 
  Search, 
  Target, 
  MessageSquare, 
  Shield, 
  Zap,
  Sparkles,
  GitBranch,
  Database,
  Server,
  Code2,
  BarChart3,
  Globe
} from 'lucide-react'
import AgentFlow from './components/AgentFlow'
import DemoPanel from './components/DemoPanel'
import MetricsPanel from './components/MetricsPanel'

function App() {
  const [activeAgent, setActiveAgent] = useState(0)
  const [isScrolled, setIsScrolled] = useState(false)

  useEffect(() => {
    const handleScroll = () => setIsScrolled(window.scrollY > 50)
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const agents = [
    { 
      id: 0, 
      name: 'ExtractorAgent', 
      icon: Search, 
      color: '#6366f1',
      desc: 'Trích xuất đặc trưng từ CV & JD',
      detail: 'Phân tích cấu trúc CV, trích xuất skills, kinh nghiệm, học vấn. Chuẩn hóa dữ liệu đầu vào cho pipeline AI.'
    },
    { 
      id: 1, 
      name: 'RAGAgent', 
      icon: Database, 
      color: '#8b5cf6',
      desc: 'Truy xuất tri thức từ corpus',
      detail: 'Tìm kiếm vector trong pgvector, truy xuất tài liệu văn hóa Hàn, best practices, và company knowledge.'
    },
    { 
      id: 2, 
      name: 'MatcherAgent', 
      icon: Target, 
      color: '#06b6d4',
      desc: 'Tính toán độ phù hợp',
      detail: 'So khớp skills overlap, tính fit score dựa trên vector similarity và rule-based scoring.'
    },
    { 
      id: 3, 
      name: 'ExplainerAgent', 
      icon: MessageSquare, 
      color: '#10b981',
      desc: 'Tạo lý giải có dẫn chứng',
      detail: 'Sinh reasoning chain đa ngôn ngữ (EN/KR/VN) với citations từ evidence sources.'
    },
    { 
      id: 4, 
      name: 'CriticAgent', 
      icon: Shield, 
      color: '#f59e0b',
      desc: 'Kiểm tra & điều chỉnh',
      detail: 'Validate confidence, điều chỉnh edge cases, kích hoạt fallback khi AI không đủ tin cậy.'
    },
  ]

  const features = [
    { icon: Brain, title: 'Multi-Agent AI', desc: '5 agent chuyên biệt phối hợp xử lý CV matching' },
    { icon: GitBranch, title: 'Agentic Workflow', desc: 'Pipeline có planning, memory và self-correction' },
    { icon: Database, title: 'RAG + pgvector', desc: 'Grounding với vector database và citations' },
    { icon: MessageSquare, title: 'Explainability', desc: 'Giải thích lý do match với dẫn chứng cụ thể' },
    { icon: Shield, title: 'Guardrails', desc: 'PII redaction, bias detection, safety checks' },
    { icon: BarChart3, title: 'Evaluation Suite', desc: 'Precision@K, nDCG, MAE, RMSE benchmarks' },
  ]

  const techStack = [
    { icon: Code2, name: 'Laravel', role: 'Backend API', color: '#ff2d20' },
    { icon: Server, name: 'FastAPI', role: 'AI Service', color: '#009688' },
    { icon: Database, name: 'PostgreSQL', role: 'pgvector DB', color: '#336791' },
    { icon: Brain, name: 'OpenAI', role: 'LLM Engine', color: '#10a37f' },
    { icon: Zap, name: 'React', role: 'Frontend', color: '#61dafb' },
    { icon: Globe, name: 'Docker', role: 'Containerization', color: '#2496ed' },
  ]

  return (
    <div className="min-h-screen bg-dark text-white overflow-x-hidden">
      {/* Navbar */}
      <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled ? 'glass py-3' : 'py-5'
      }`}>
        <div className="max-w-7xl mx-auto px-6 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Sparkles className="w-6 h-6 text-primary" />
            <span className="text-xl font-bold gradient-text">Smart CV Matcher</span>
          </div>
          <div className="hidden md:flex items-center gap-6 text-sm text-gray-400">
            <a href="#agents" className="hover:text-white transition">Agents</a>
            <a href="#demo" className="hover:text-white transition">Demo</a>
            <a href="#features" className="hover:text-white transition">Features</a>
            <a href="#metrics" className="hover:text-white transition">Metrics</a>
          </div>
          <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-500/10 border border-green-500/20">
            <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
            <span className="text-xs text-green-400">Live</span>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative pt-32 pb-20 px-6">
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-1/4 w-96 h-96 bg-primary/20 rounded-full blur-3xl" />
          <div className="absolute bottom-20 right-1/4 w-96 h-96 bg-secondary/20 rounded-full blur-3xl" />
        </div>
        
        <div className="relative max-w-7xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-sm mb-6">
              <Zap className="w-4 h-4 text-accent" />
              <span className="text-gray-300">Level 5 AI Architecture</span>
            </div>
            
            <h1 className="text-5xl md:text-7xl font-bold mb-6">
              <span className="gradient-text">Smart CV Matcher</span>
              <br />
              <span className="text-white">AI System</span>
            </h1>
            
            <p className="text-xl text-gray-400 max-w-2xl mx-auto mb-10">
              Hệ thống AI đa tác nhân cho CV-JD matching với RAG, Explainability, 
              và Guardrails. Đạt chuẩn Level 5 AI cho hackathon.
            </p>
            
            <div className="flex flex-wrap justify-center gap-4">
              <a href="#demo" className="px-8 py-3 rounded-xl bg-primary hover:bg-primary-dark transition glow text-white font-medium flex items-center gap-2">
                <Sparkles className="w-5 h-5" />
                Thử Demo
              </a>
              <a href="#agents" className="px-8 py-3 rounded-xl glass hover:bg-white/10 transition text-white font-medium flex items-center gap-2">
                <Brain className="w-5 h-5" />
                Xem Agents
              </a>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Agent Flow Visualization */}
      <section id="agents" className="py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              <span className="gradient-text">Multi-Agent Architecture</span>
            </h2>
            <p className="text-gray-400 max-w-xl mx-auto">
              5 agent chuyên biệt phối hợp theo pipeline có planning, memory và self-correction
            </p>
          </div>

          <AgentFlow agents={agents} activeAgent={activeAgent} setActiveAgent={setActiveAgent} />

          {/* Agent Detail Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-12">
            {agents.map((agent, idx) => {
              const Icon = agent.icon
              return (
                <motion.div
                  key={agent.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: idx * 0.1 }}
                  onClick={() => setActiveAgent(idx)}
                  className={`agent-card p-6 rounded-2xl cursor-pointer ${
                    activeAgent === idx 
                      ? 'bg-primary/10 border border-primary/30' 
                      : 'glass'
                  }`}
                >
                  <div className="flex items-center gap-3 mb-4">
                    <div 
                      className="w-10 h-10 rounded-xl flex items-center justify-center"
                      style={{ backgroundColor: `${agent.color}20` }}
                    >
                      <Icon className="w-5 h-5" style={{ color: agent.color }} />
                    </div>
                    <div>
                      <h3 className="font-semibold">{agent.name}</h3>
                      <p className="text-xs text-gray-500">{agent.desc}</p>
                    </div>
                  </div>
                  <p className="text-sm text-gray-400 leading-relaxed">{agent.detail}</p>
                </motion.div>
              )
            })}
          </div>
        </div>
      </section>

      {/* Demo Section */}
      <section id="demo" className="py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              <span className="gradient-text">Live Demo</span>
            </h2>
            <p className="text-gray-400 max-w-xl mx-auto">
              Thử nghiệm API matching với candidate và job thực tế
            </p>
          </div>
          <DemoPanel />
        </div>
      </section>

      {/* Features Grid */}
      <section id="features" className="py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              <span className="gradient-text">Key Features</span>
            </h2>
            <p className="text-gray-400 max-w-xl mx-auto">
              Các tính năng cốt lõi giúp hệ thống đạt Level 5 AI
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((feature, idx) => {
              const Icon = feature.icon
              return (
                <motion.div
                  key={idx}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: idx * 0.1 }}
                  className="agent-card p-6 rounded-2xl glass"
                >
                  <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-4">
                    <Icon className="w-6 h-6 text-primary" />
                  </div>
                  <h3 className="font-semibold mb-2">{feature.title}</h3>
                  <p className="text-sm text-gray-400">{feature.desc}</p>
                </motion.div>
              )
            })}
          </div>
        </div>
      </section>

      {/* Metrics Section */}
      <section id="metrics" className="py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              <span className="gradient-text">Performance Metrics</span>
            </h2>
            <p className="text-gray-400 max-w-xl mx-auto">
              Benchmark suite đánh giá hiệu suất AI với metrics chuẩn
            </p>
          </div>
          <MetricsPanel />
        </div>
      </section>

      {/* Tech Stack */}
      <section className="py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              <span className="gradient-text">Tech Stack</span>
            </h2>
            <p className="text-gray-400 max-w-xl mx-auto">
              Công nghệ hiện đại cho hệ thống scalable và reproducible
            </p>
          </div>

          <div className="flex flex-wrap justify-center gap-6">
            {techStack.map((tech, idx) => {
              const Icon = tech.icon
              return (
                <motion.div
                  key={idx}
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ delay: idx * 0.1 }}
                  className="agent-card p-6 rounded-2xl glass flex flex-col items-center gap-3 w-40"
                >
                  <Icon className="w-8 h-8" style={{ color: tech.color }} />
                  <div className="text-center">
                    <h4 className="font-semibold text-sm">{tech.name}</h4>
                    <p className="text-xs text-gray-500">{tech.role}</p>
                  </div>
                </motion.div>
              )
            })}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-10 px-6 border-t border-white/10">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-primary" />
            <span className="font-semibold">Smart CV Matcher</span>
          </div>
          <p className="text-sm text-gray-500">
            Level 5 AI Architecture for Hackathon
          </p>
          <div className="flex items-center gap-4 text-sm text-gray-500">
            <span>API: <span className="text-green-400">Online</span></span>
            <span>v0.1.0</span>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default App
