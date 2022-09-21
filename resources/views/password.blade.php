<div style="font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border: solid 1px #e5e5e5;border-radius: 8px;margin-top: 2rem;margin-bottom:25px;box-shadow: 0 10px 25px rgba(92,99,105,.2);width: 500px; margin-left: auto !important;margin-right: auto !important;flex: 0 0 33.333333%;width:90%;max-width:630px;position: relative;">
    <div style=" margin: 0; display: flex;flex-wrap: wrap; padding: .5rem !important; background-color: #0b283f;border-top-right-radius: 5px; border-top-left-radius: 5px; ">
        <img style="width: 200px;height: 35px;margin: auto !important;border-style: none;" title="logoueb" alt="logoueb" src="https://sac.ueb.edu.ec/logo_white.svg">
    </div>
    <br>
    <div id="content">
        <div>
            <!-- <span style="font-size:13px; text-aling:left;">Fecha: jueves, 12 de noviembre de 2020 15:58</span><br><br>-->
            <span style=" text-aling:left;">Estimado/a: <b>{{ $data['name']}}</b></span><br><br>
            <span>Si solicitó un restablecimiento de contraseña para <b>{{ $data['email']}}</b>, haga clic en el botón a continuación. Si no hizo esta solicitud, ignore este correo electrónico.</span><br>

        </div><br>
        <div><center><a style=" display: inline-block;
    font-weight: 400;
    text-align: center;
    text-decoration:none;
    vertical-align: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: .375rem .75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: .25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    color: #bf0811;
    border-color: #bf0811 !important;"
                        href="https://sac.ueb.edu.ec/password/reset/{{$data['token']}}?user={{$data['cedula']}}" class=" btn-outline-sac w-100">
                    Restablecer contraseña</a></center>
        </div><br>
        <span>Respondemos todas tus preguntas, escríbenos a <a href="mailto:dtics@ueb.edu.ec?subject=Tengo dudas sobre los Sistemas Virtuales de la UEB" style="color:#058ecd" target="_blank">dtics@ueb.edu.ec</a></span>

        <br><br><br><span   style="text-aling:left;">Gracias por utilizar nuestros servicios.</span><br><br>
        <span   style="text-aling:left;"> Atentamente,</span><br><br>
        <span  style=" text-aling:left;"><b> Dirección de Tecnología de la Información y la Comunicación</b></span>
        <br><br>
        <div style=" color: white; margin: 0; display: flex;flex-wrap: wrap; padding: .5rem !important; background-color: #0b283f;border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; ">
            <center style="width: 100%;font-size: 10px;"><span>Copyright © 2021<br> Derechos Reservados Universidad Estatal de Bolívar<br> Dirección de TICs </span></center>
        </div>
    </div>
</div>
