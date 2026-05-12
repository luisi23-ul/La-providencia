<table style="width: 100%; max-width: 450px; margin: 40px auto; background-color: white; border-collapse: collapse; font-family: sans-serif; border: 1px solid #0052d4; border-radius: 15px;">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #0052d4; text-align: center; margin-top: 0;">Crear Cuenta</h2>
            <p style="text-align: center; color: #666; font-size: 0.9em; margin-bottom: 25px;">La Providencia - Registro de Cliente</p>
            
            <form id="formRegistro" action="index.php?action=procesar_registro" method="POST">
                
                <label style="font-weight: bold; color: #333;">Nombre</label><br>
                <input type="text" name="nombre" style="width: 100%; padding: 10px; margin: 8px 0 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">

                <label style="font-weight: bold; color: #333;">Apellido</label><br>
                <input type="text" name="apellido" style="width: 100%; padding: 10px; margin: 8px 0 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">

                <label style="font-weight: bold; color: #333;">Teléfono</label><br>
                <input type="text" name="telefono" style="width: 100%; padding: 10px; margin: 8px 0 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">

                <label style="font-weight: bold; color: #333;">Correo Electrónico</label><br>
                <input type="email" name="correo" style="width: 100%; padding: 10px; margin: 8px 0 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">

                <label style="font-weight: bold; color: #333;">Contraseña</label><br>
                <input type="password" name="clave" style="width: 100%; padding: 10px; margin: 8px 0 20px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">

                <input type="button" value="REGISTRARME" onclick="document.getElementById('formRegistro').submit();" style="width: 100%; background-color: #0052d4; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1em;">
            </form>

            <p style="text-align: center; margin-top: 20px;">
                <a href="index.php?action=ver_catalogo" style="color: #666; text-decoration: none; font-size: 0.9em;">← Volver al Catálogo</a>
            </p>
        </td>
    </tr>
</table>