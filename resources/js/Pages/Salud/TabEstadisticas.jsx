// Vacía por ahora — placeholder visual para el MVP
export default function TabEstadisticas() {
    return (
        <>
            <div className="card-header">
                <h2>Estadísticas</h2>
                <small>Resumen de salud del hato</small>
            </div>
            <div style={{
                display:'flex', flexDirection:'column', alignItems:'center',
                justifyContent:'center', padding:'4rem 2rem', gap:'1rem', color:'#9ca3af'
            }}>
                <span style={{ fontSize:'2.5rem' }}>📊</span>
                <p style={{ fontWeight:600, color:'#6b7280' }}>Próximamente</p>
                <p style={{ fontSize:'0.875rem', textAlign:'center' }}>
                    Aquí verás gráficas de cobertura de vacunación, historial de enfermedades y tendencias de salud por especie.
                </p>
            </div>
        </>
    );
}