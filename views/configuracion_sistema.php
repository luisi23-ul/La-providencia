<style>
    .admin-form { max-width: 800px; margin: 20px auto; padding: 30px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; font-family: sans-serif; }
    h2 { color: #1e293b; font-size: 1.25rem; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
    fieldset { border: none; margin: 0 0 30px 0; padding: 0; }
    label { display: block; margin: 15px 0 5px; font-weight: 600; color: #475569; font-size: 0.9rem; }
    input[type="text"], textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; box-sizing: border-box; background: #f8fafc; }
    ul { list-style: none; padding: 0; }
    li { display: flex; align-items: center; gap: 20px; margin-bottom: 15px; }
    input[type="color"] { border: none; width: 40px; height: 40px; cursor: pointer; border-radius: 4px; }
    
    /* ESTILO DEL BOTÓN INTEGRADO */
    .btn-submit {
        background-color: #0052d4;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        margin-top: 20px;
        font-size: 1rem;
        transition: background 0.3s;
    }
    .btn-submit:hover { background-color: #003a96; }
</style>

<form action="index.php?action=actualizar_configuracion" method="POST" class="admin-form">
    <section>
        <h2>Configuración General</h2>
        <fieldset>
            <label>Título Hero</label>
            <input type="text" name="titulo_hero" value="<?php echo htmlspecialchars($config->titulo_hero); ?>">
            
            <label>Subtítulo Hero</label>
            <textarea name="subtitulo_hero"><?php echo htmlspecialchars($config->subtitulo_hero); ?></textarea>
            
            <label>Dirección</label>
            <input type="text" name="direccion" value="<?php echo htmlspecialchars($config->direccion); ?>">
            
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($config->telefono); ?>">

            
            
            <label>Texto Footer</label>
            <textarea name="footer_texto"><?php echo htmlspecialchars($config->footer_texto); ?></textarea>
        </fieldset>
    </section>

    <section>
        <h2>Paleta de Colores</h2>
        <ul>
            <li>
                <input type="color" name="color_primario" value="<?php echo $config->color_primario; ?>">
                <label>Primario (Azul Marca)</label>
            </li>
            <li>
                <input type="color" name="color_secundario" value="<?php echo $config->color_secundario; ?>">
                <label>Secundario (Oscuro)</label>
            </li>
            <li>
                <input type="color" name="color_terciario" value="<?php echo $config->color_terciario; ?>">
                <label>Terciario (Footer)</label>
            </li>
            <li>
                <input type="color" name="color_cuaternario" value="<?php echo $config->color_cuaternario; ?>">
                <label>Cuaternario (Texto Muted)</label>
            </li>
        </ul>
    </section>

    <button type="submit" class="btn-submit">Guardar Cambios</button>
</form>