import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../shared/widgets/brand_logo.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    await ref
        .read(appControllerProvider.notifier)
        .login(_emailController.text, _passwordController.text);
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(appControllerProvider);
    return Scaffold(
      body: Stack(
        children: [
          const _LoginBackdrop(),
          SafeArea(
            child: Column(
              children: [
                Expanded(
                  child: Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(24),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 440),
                        child: TweenAnimationBuilder<double>(
                          tween: Tween(begin: 0, end: 1),
                          duration: const Duration(milliseconds: 650),
                          curve: Curves.easeOutCubic,
                          builder: (context, value, child) => Opacity(
                            opacity: value,
                            child: Transform.translate(
                              offset: Offset(0, 24 * (1 - value)),
                              child: child,
                            ),
                          ),
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.fromLTRB(
                                24,
                                30,
                                24,
                                26,
                              ),
                              child: Form(
                                key: _formKey,
                                child: Column(
                                  children: [
                                    const BrandLogo(size: 86),
                                    const SizedBox(height: 18),
                                    Text(
                                      'مركز الغفران',
                                      style: Theme.of(context)
                                          .textTheme
                                          .headlineSmall
                                          ?.copyWith(
                                            fontWeight: FontWeight.w800,
                                            color: AppTheme.ink,
                                          ),
                                    ),
                                    const SizedBox(height: 5),
                                    const Text(
                                      'تسجيل الحضور والتسميع اليومي',
                                      style: TextStyle(
                                        color: Color(0xFF64766F),
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    const SizedBox(height: 28),
                                    TextFormField(
                                      controller: _emailController,
                                      keyboardType: TextInputType.emailAddress,
                                      textDirection: TextDirection.ltr,
                                      textAlign: TextAlign.right,
                                      decoration: const InputDecoration(
                                        labelText: 'البريد الإلكتروني',
                                        prefixIcon: Icon(
                                          Icons.alternate_email_rounded,
                                        ),
                                      ),
                                      validator: (value) =>
                                          value == null || !value.contains('@')
                                          ? 'أدخل بريدًا إلكترونيًا صحيحًا.'
                                          : null,
                                    ),
                                    const SizedBox(height: 14),
                                    TextFormField(
                                      controller: _passwordController,
                                      obscureText: _obscurePassword,
                                      textDirection: TextDirection.ltr,
                                      textAlign: TextAlign.right,
                                      decoration: InputDecoration(
                                        labelText: 'كلمة المرور',
                                        prefixIcon: const Icon(
                                          Icons.lock_outline_rounded,
                                        ),
                                        suffixIcon: IconButton(
                                          onPressed: () => setState(
                                            () => _obscurePassword =
                                                !_obscurePassword,
                                          ),
                                          icon: Icon(
                                            _obscurePassword
                                                ? Icons.visibility_outlined
                                                : Icons.visibility_off_outlined,
                                          ),
                                        ),
                                      ),
                                      validator: (value) =>
                                          value == null || value.length < 8
                                          ? 'أدخل كلمة المرور كاملة.'
                                          : null,
                                      onFieldSubmitted: (_) =>
                                          state.busy ? null : _submit(),
                                    ),
                                    AnimatedSize(
                                      duration: const Duration(
                                        milliseconds: 250,
                                      ),
                                      child: state.error == null
                                          ? const SizedBox(height: 22)
                                          : Container(
                                              width: double.infinity,
                                              margin: const EdgeInsets.only(
                                                top: 16,
                                                bottom: 6,
                                              ),
                                              padding: const EdgeInsets.all(12),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFFFECEA),
                                                borderRadius:
                                                    BorderRadius.circular(14),
                                              ),
                                              child: Text(
                                                state.error!,
                                                style: const TextStyle(
                                                  color: Color(0xFF9A2D25),
                                                  fontWeight: FontWeight.w700,
                                                ),
                                              ),
                                            ),
                                    ),
                                    SizedBox(
                                      width: double.infinity,
                                      child: FilledButton.icon(
                                        onPressed: state.busy ? null : _submit,
                                        icon: state.busy
                                            ? const SizedBox.square(
                                                dimension: 20,
                                                child:
                                                    CircularProgressIndicator(
                                                      strokeWidth: 2,
                                                    ),
                                              )
                                            : const Icon(Icons.login_rounded),
                                        label: Text(
                                          state.busy
                                              ? 'جارٍ تسجيل الدخول...'
                                              : 'دخول المحفّظ',
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 14),
                                    const Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        Icon(
                                          Icons.offline_bolt_rounded,
                                          size: 18,
                                          color: AppTheme.emerald,
                                        ),
                                        SizedBox(width: 6),
                                        Text(
                                          'يمكنك العمل لاحقًا دون اتصال',
                                          style: TextStyle(fontSize: 12),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LoginBackdrop extends StatelessWidget {
  const _LoginBackdrop();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFFE6F3EC), Color(0xFFFFFBEB), Color(0xFFF4F8F5)],
        ),
      ),
      child: const SizedBox.expand(),
    );
  }
}
