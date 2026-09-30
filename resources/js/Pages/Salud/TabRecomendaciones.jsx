// Placeholder — en el futuro mostrará sugerencias por especie/edad
export default function TabRecomendaciones({ vacunas = [] }) {
    return (
        <>
            <div className="card-header">
                <h2>Recomendaciones por Especie</h2>
                <small>Programas sugeridos según edad y especie</small>
            </div>
            <div style={{
                display:'flex', flexDirection:'column', alignItems:'center',
                justifyContent:'center', padding:'4rem 2rem', gap:'1rem', color:'#9ca3af'
            }}>
                <span style={{ fontSize:'2.5rem' }}>⚡</span>
                <p style={{ fontWeight:600, color:'#6b7280' }}>Próximamente</p>
                <p style={{ fontSize:'0.875rem', textAlign:'center' }}>
                    Sugerencias automáticas de vacunación basadas en la especie, edad y estado productivo de cada animal.
                </p>
                {vacunas.length > 0 && (
                    <div style={{ marginTop:'1rem', width:'100%' }}>
                        <p style={{ fontSize:'0.75rem', color:'#6b7280', marginBottom:'0.5rem', textAlign:'center' }}>
                            Vacunas disponibles en el sistema:
                        </p>
                        <div style={{ display:'flex', flexWrap:'wrap', gap:'0.5rem', justifyContent:'center' }}>
                            {vacunas.map(v => (
                                <span key={v.id} className="badge gray">{v.nombre}</span>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}