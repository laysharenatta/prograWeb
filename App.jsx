import { useState, useEffect } from 'react'
import Login from './components/Login'
import Dashboard from './components/Dashboard'
import PromoPage from './components/PromoPage'
import './App.css'

function App() {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const [currentView, setCurrentView] = useState('dashboard') // 'dashboard' o 'promo'

  // Verificar si hay una sesión activa al cargar la aplicación
  useEffect(() => {
    checkSession()
  }, [])

  const checkSession = async () => {
    try {
      const response = await fetch('http://localhost:8000/auth/check-session', {
        credentials: 'include'
      })
      const data = await response.json()
      
      if (data.authenticated) {
        setUser(data.user)
      }
    } catch (err) {
      console.error('Error al verificar sesión:', err)
    } finally {
      setLoading(false)
    }
  }

  const handleLogin = (userData) => {
    setUser(userData)
    setCurrentView('dashboard')
  }

  const handleLogout = () => {
    setUser(null)
    setCurrentView('dashboard')
  }

  const navigateToPromo = () => {
    setCurrentView('promo')
  }

  const navigateToDashboard = () => {
    setCurrentView('dashboard')
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
          <p className="text-white text-lg">Cargando...</p>
        </div>
      </div>
    )
  }

  // Si no hay usuario autenticado, mostrar login
  if (!user) {
    return <Login onLogin={handleLogin} />
  }

  // Si hay usuario autenticado, mostrar la vista correspondiente
  if (currentView === 'promo') {
    return (
      <PromoPage 
        user={user} 
        onBackToDashboard={navigateToDashboard}
        onLogout={handleLogout}
      />
    )
  }

  return (
    <Dashboard 
      user={user} 
      onLogout={handleLogout}
      onNavigateToPromo={navigateToPromo}
    />
  )
}

export default App

